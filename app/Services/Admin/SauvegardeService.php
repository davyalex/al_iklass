<?php

namespace App\Services\Admin;

use App\Models\Parametre;
use Carbon\Carbon;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SauvegardeService
{
    private const PREFIXE_FICHIER = 'al-iklass_';

    private const EXTENSION = '.sql.gz';

    /**
     * Dossier où sont écrites les sauvegardes : le dossier personnalisé
     * défini dans Paramètres (potentiellement hors application, ex. un
     * point de montage réseau) s'il est renseigné, sinon un dossier privé
     * du projet (storage/app/private, non accessible publiquement et non
     * versionné dans Git).
     */
    public function dossier(): string
    {
        $personnalise = trim((string) Parametre::valeur('sauvegarde.dossier_personnalise', ''));

        return $personnalise !== '' ? rtrim($personnalise, '/\\') : $this->dossierParDefaut();
    }

    public function dossierParDefaut(): string
    {
        return storage_path('app/private/backups');
    }

    /**
     * @return array{nom: string, taille: int, date: Carbon}[] triés du plus récent au plus ancien
     */
    public function lister(): array
    {
        $dossier = $this->dossier();

        if (! is_dir($dossier)) {
            return [];
        }

        $fichiers = glob($dossier.DIRECTORY_SEPARATOR.self::PREFIXE_FICHIER.'*'.self::EXTENSION) ?: [];

        $sauvegardes = array_map(fn (string $chemin) => [
            'nom' => basename($chemin),
            'taille' => filesize($chemin),
            'date' => Carbon::createFromTimestamp(filemtime($chemin)),
        ], $fichiers);

        usort($sauvegardes, fn (array $a, array $b) => $b['date']->timestamp <=> $a['date']->timestamp);

        return $sauvegardes;
    }

    /**
     * Sauvegarde la base courante (mysqldump compressé à la volée). Retourne
     * le nom du fichier créé.
     */
    public function creer(): string
    {
        $config = $this->configConnexion();
        $dossier = $this->dossier();

        if (! is_dir($dossier) && ! mkdir($dossier, 0755, true) && ! is_dir($dossier)) {
            throw new RuntimeException("Impossible de créer le dossier de sauvegarde : {$dossier}");
        }

        $nom = self::PREFIXE_FICHIER.now()->format('Y-m-d_His').self::EXTENSION;
        $chemin = $dossier.DIRECTORY_SEPARATOR.$nom;

        $process = new Process($this->commandeMysqldump($config));
        $process->setEnv($this->envProcessus($config));
        $process->setTimeout(600);

        $flotGzip = gzopen($chemin, 'wb9');

        if ($flotGzip === false) {
            throw new RuntimeException("Impossible d'écrire le fichier de sauvegarde : {$chemin}");
        }

        try {
            $process->run(function (string $type, string $buffer) use ($flotGzip): void {
                if ($type === Process::OUT) {
                    gzwrite($flotGzip, $buffer);
                }
            });
        } finally {
            gzclose($flotGzip);
        }

        if (! $process->isSuccessful()) {
            @unlink($chemin);

            throw new ProcessFailedException($process);
        }

        return $nom;
    }

    /**
     * Ne garde que les N sauvegardes les plus récentes (N = paramètre
     * "sauvegarde.retention"). Retourne le nombre de fichiers supprimés.
     */
    public function purger(): int
    {
        $retention = max(1, (int) Parametre::valeur('sauvegarde.retention', '5'));
        $dossier = $this->dossier();

        $aSupprimer = array_slice($this->lister(), $retention);

        foreach ($aSupprimer as $sauvegarde) {
            @unlink($dossier.DIRECTORY_SEPARATOR.$sauvegarde['nom']);
        }

        return count($aSupprimer);
    }

    /**
     * Restaure intégralement la base à partir d'une sauvegarde existante.
     * Écrase toutes les données actuelles — destructif et irréversible.
     */
    public function restaurer(string $nomFichier): void
    {
        $chemin = $this->cheminSecurise($nomFichier);
        $config = $this->configConnexion();

        $sqlTemporaire = tempnam(sys_get_temp_dir(), 'aliklass_restore_');

        if ($sqlTemporaire === false) {
            throw new RuntimeException('Impossible de créer un fichier temporaire pour la restauration.');
        }

        try {
            $this->decompresser($chemin, $sqlTemporaire);

            $flotEntree = fopen($sqlTemporaire, 'rb');

            if ($flotEntree === false) {
                throw new RuntimeException('Impossible de lire le fichier de sauvegarde décompressé.');
            }

            $process = new Process($this->commandeMysql($config));
            $process->setEnv($this->envProcessus($config));
            $process->setTimeout(900);
            $process->setInput($flotEntree);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } finally {
            @unlink($sqlTemporaire);
        }
    }

    /**
     * Chemin absolu et vérifié d'une sauvegarde, sans jamais laisser un nom
     * de fichier fourni par l'utilisateur sortir du dossier de sauvegarde
     * (traversée de répertoire).
     */
    public function cheminSecurise(string $nomFichier): string
    {
        $nomFichier = basename($nomFichier);
        $chemin = $this->dossier().DIRECTORY_SEPARATOR.$nomFichier;

        if (! str_starts_with($nomFichier, self::PREFIXE_FICHIER) || ! is_file($chemin)) {
            throw new RuntimeException("Fichier de sauvegarde introuvable : {$nomFichier}");
        }

        return $chemin;
    }

    /**
     * @return array{driver: string, host: string, port: string, database: string, username: string, password: string}
     */
    private function configConnexion(): array
    {
        $connexion = config('database.default');
        $config = config("database.connections.{$connexion}");

        if (! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Sauvegarde non supportée pour le pilote de base de données « {$config['driver']} » (mysqldump requiert MySQL/MariaDB).");
        }

        return $config;
    }

    /**
     * Variables d'environnement du sous-processus mysqldump/mysql. Sur
     * Windows, Symfony Process filtre l'environnement hérité pour ne garder
     * que les clés déjà présentes dans $_SERVER — ce qui, lors d'un appel
     * déclenché depuis une requête web (Apache/PHP-FPM, contrairement au
     * CLI), retire "SystemRoot" et empêche mysqldump.exe d'initialiser
     * Winsock ("Can't create TCP/IP socket"). On le réinjecte explicitement.
     *
     * @param  array{password: string}  $config
     * @return array<string, string>
     */
    private function envProcessus(array $config): array
    {
        $env = ['MYSQL_PWD' => $config['password']];

        $systemRoot = getenv('SystemRoot');

        if ($systemRoot !== false) {
            $env['SystemRoot'] = $systemRoot;
        }

        return $env;
    }

    /**
     * @param  array{host: string, port: string, database: string, username: string}  $config
     * @return string[]
     */
    private function commandeMysqldump(array $config): array
    {
        return [
            config('sauvegarde.mysqldump_path'),
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            $config['database'],
        ];
    }

    /**
     * @param  array{host: string, port: string, database: string, username: string}  $config
     * @return string[]
     */
    private function commandeMysql(array $config): array
    {
        return [
            config('sauvegarde.mysql_path'),
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            $config['database'],
        ];
    }

    private function decompresser(string $cheminGz, string $cheminSql): void
    {
        $lecture = gzopen($cheminGz, 'rb');
        $ecriture = fopen($cheminSql, 'wb');

        if ($lecture === false || $ecriture === false) {
            throw new RuntimeException('Impossible de décompresser le fichier de sauvegarde.');
        }

        while (! gzeof($lecture)) {
            fwrite($ecriture, gzread($lecture, 1024 * 1024));
        }

        gzclose($lecture);
        fclose($ecriture);
    }
}
