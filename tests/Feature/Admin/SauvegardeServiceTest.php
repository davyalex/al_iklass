<?php

namespace Tests\Feature\Admin;

use App\Models\Parametre;
use App\Services\Admin\SauvegardeService;
use Database\Seeders\ParametreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SauvegardeServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $dossierTest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametreSeeder::class);

        $this->dossierTest = sys_get_temp_dir().'/aliklass_sauvegardes_test_'.uniqid();
        mkdir($this->dossierTest);
        Parametre::definir('sauvegarde.dossier_personnalise', $this->dossierTest);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dossierTest.'/*') ?: [] as $fichier) {
            @unlink($fichier);
        }
        @rmdir($this->dossierTest);

        parent::tearDown();
    }

    public function test_dossier_utilise_le_chemin_personnalise_quand_defini(): void
    {
        $service = app(SauvegardeService::class);

        $this->assertSame($this->dossierTest, $service->dossier());
    }

    public function test_dossier_utilise_le_chemin_par_defaut_quand_vide(): void
    {
        Parametre::definir('sauvegarde.dossier_personnalise', '');
        $service = app(SauvegardeService::class);

        $this->assertSame(storage_path('app/private/backups'), $service->dossier());
    }

    public function test_lister_trie_les_sauvegardes_de_la_plus_recente_a_la_plus_ancienne(): void
    {
        $this->creerFichierFactice('al-iklass_2026-01-01_020000.sql.gz', touch: 1000);
        $this->creerFichierFactice('al-iklass_2026-01-03_020000.sql.gz', touch: 3000);
        $this->creerFichierFactice('al-iklass_2026-01-02_020000.sql.gz', touch: 2000);

        $sauvegardes = app(SauvegardeService::class)->lister();

        $this->assertSame([
            'al-iklass_2026-01-03_020000.sql.gz',
            'al-iklass_2026-01-02_020000.sql.gz',
            'al-iklass_2026-01-01_020000.sql.gz',
        ], array_column($sauvegardes, 'nom'));
    }

    public function test_purger_ne_garde_que_les_n_sauvegardes_les_plus_recentes(): void
    {
        Parametre::definir('sauvegarde.retention', '2');

        $this->creerFichierFactice('al-iklass_2026-01-01_020000.sql.gz', touch: 1000);
        $this->creerFichierFactice('al-iklass_2026-01-02_020000.sql.gz', touch: 2000);
        $this->creerFichierFactice('al-iklass_2026-01-03_020000.sql.gz', touch: 3000);

        $service = app(SauvegardeService::class);
        $supprimes = $service->purger();

        $this->assertSame(1, $supprimes);
        $this->assertSame(
            ['al-iklass_2026-01-03_020000.sql.gz', 'al-iklass_2026-01-02_020000.sql.gz'],
            array_column($service->lister(), 'nom')
        );
    }

    public function test_chemin_securise_rejette_une_tentative_de_traversee_de_repertoire(): void
    {
        $this->expectException(RuntimeException::class);

        app(SauvegardeService::class)->cheminSecurise('../../etc/passwd');
    }

    public function test_chemin_securise_rejette_un_fichier_inexistant(): void
    {
        $this->expectException(RuntimeException::class);

        app(SauvegardeService::class)->cheminSecurise('al-iklass_2099-01-01_000000.sql.gz');
    }

    public function test_chemin_securise_accepte_une_sauvegarde_existante(): void
    {
        $this->creerFichierFactice('al-iklass_2026-01-01_020000.sql.gz');

        $chemin = app(SauvegardeService::class)->cheminSecurise('al-iklass_2026-01-01_020000.sql.gz');

        $this->assertSame($this->dossierTest.DIRECTORY_SEPARATOR.'al-iklass_2026-01-01_020000.sql.gz', $chemin);
    }

    private function creerFichierFactice(string $nom, int $touch = 0): void
    {
        $chemin = $this->dossierTest.DIRECTORY_SEPARATOR.$nom;
        file_put_contents($chemin, 'contenu-factice');

        if ($touch > 0) {
            touch($chemin, $touch);
        }
    }
}
