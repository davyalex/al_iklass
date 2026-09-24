<?php

namespace App\Services\Admin;

use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Journal d'audit automatique : toute création, modification, archivage ou
 * restauration d'un modèle applicatif (App\Models) est enregistrée, quel que
 * soit l'utilisateur qui la déclenche. Branché une seule fois sur les
 * événements Eloquent (AppServiceProvider) : un nouveau modèle est donc
 * audité sans rien ajouter.
 *
 * Les actions à forte valeur métier (création de compte, changement de rôle,
 * réinitialisation de mot de passe...) restent journalisées explicitement
 * dans leur Service avec une description plus parlante.
 */
class AuditService
{
    public const LOG_MODELE = 'modele';

    public const LOG_AUTHENTIFICATION = 'authentification';

    /**
     * Libellé lisible de chaque type d'action, affiché en badge dans le journal.
     *
     * @var array<string, array{libelle: string, couleur: string}>
     */
    public const EVENEMENTS = [
        'created' => ['libelle' => 'Création', 'couleur' => 'success'],
        'updated' => ['libelle' => 'Modification', 'couleur' => 'primary'],
        'deleted' => ['libelle' => 'Suppression', 'couleur' => 'danger'],
        'restored' => ['libelle' => 'Restauration', 'couleur' => 'info'],
        'connexion' => ['libelle' => 'Connexion', 'couleur' => 'secondary'],
        'deconnexion' => ['libelle' => 'Déconnexion', 'couleur' => 'secondary'],
        'purge' => ['libelle' => 'Purge', 'couleur' => 'warning'],
    ];

    /**
     * Libellé français des modèles (à défaut : nom de classe mis en forme).
     *
     * @var array<string, string>
     */
    private const LIBELLES_MODELES = [
        'Achat' => 'Achat',
        'AchatLigne' => "Ligne d'achat",
        'Article' => 'Article',
        'BonCommande' => 'Bon de commande',
        'BonCommandeLigne' => 'Ligne de bon de commande',
        'Caisse' => 'Caisse',
        'CategorieArticle' => "Catégorie d'article",
        'Financement' => 'Financement',
        'Fournisseur' => 'Fournisseur',
        'HistoriqueDette' => 'Mouvement de dette',
        'HistoriqueStatutVehicule' => 'Changement de statut véhicule',
        'Intervention' => 'Intervention',
        'Inventaire' => 'Inventaire',
        'InventaireLigne' => "Ligne d'inventaire",
        'ModePaiement' => 'Mode de paiement',
        'MouvementCaisse' => 'Mouvement de caisse',
        'MouvementStock' => 'Mouvement de stock',
        'OperationProgrammee' => 'Opération programmée',
        'PaiementFournisseur' => 'Paiement fournisseur',
        'Parametre' => 'Paramètre',
        'Preteur' => 'Prêteur',
        'RemboursementFinancement' => 'Remboursement',
        'SortieLigne' => 'Ligne de sortie',
        'SortieStock' => 'Sortie de stock',
        'StatutVehicule' => 'Statut véhicule',
        'TypeOperation' => "Type d'opération",
        'TypePanne' => 'Type de panne',
        'TypePreteur' => 'Type de prêteur',
        'Unite' => 'Unité',
        'User' => 'Utilisateur',
        'Vehicule' => 'Véhicule',
        'Versement' => 'Versement',
    ];

    /**
     * Attributs par lesquels on nomme un enregistrement dans la description,
     * par ordre de préférence.
     *
     * @var list<string>
     */
    private const ATTRIBUTS_IDENTIFIANTS = [
        'reference', 'code', 'immatriculation', 'nom', 'name', 'libelle', 'cle',
        'article_nom', 'vehicule_code', 'gestionnaire_nom', 'preteur_nom', 'fournisseur_nom',
    ];

    /**
     * Attributs techniques jamais journalisés (bruit ou données sensibles).
     *
     * @var list<string>
     */
    private const ATTRIBUTS_IGNORES = [
        'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token',
        'failed_login_attempts', 'derniere_connexion_at',
    ];

    /**
     * Modèles exclus de l'audit automatique : User est déjà journalisé
     * explicitement (UserService, ProfileController) avec des descriptions
     * métier, l'auditer aussi ici créerait des doublons.
     *
     * @var list<class-string<Model>>
     */
    private const MODELES_EXCLUS = [User::class];

    public function journaliserModele(string $evenement, Model $modele): void
    {
        if (! $this->doitEtreAudite($modele)) {
            return;
        }

        $proprietes = match ($evenement) {
            'updated' => $this->modifications($modele),
            default => ['attributes' => $this->filtrer($modele, $modele->getAttributes())],
        };

        if ($evenement === 'updated' && $proprietes['attributes'] === []) {
            return;
        }

        activity(self::LOG_MODELE)
            ->performedOn($modele)
            ->event($evenement)
            ->withProperties($proprietes)
            ->log($this->description($evenement, $modele));
    }

    public function journaliserConnexion(User $user): void
    {
        $user->forceFill(['derniere_connexion_at' => now()])->saveQuietly();

        activity(self::LOG_AUTHENTIFICATION)
            ->causedBy($user)
            ->performedOn($user)
            ->event('connexion')
            ->withProperties(['ip' => request()->ip()])
            ->log("Connexion de « {$user->name} ».");
    }

    public function journaliserDeconnexion(User $user): void
    {
        activity(self::LOG_AUTHENTIFICATION)
            ->causedBy($user)
            ->performedOn($user)
            ->event('deconnexion')
            ->log("Déconnexion de « {$user->name} ».");
    }

    /**
     * Supprime les entrées du journal plus anciennes que $jours jours et
     * trace la purge elle-même (seule entrée restante de l'opération).
     */
    public function purger(int $jours): int
    {
        $limite = now()->subDays($jours)->startOfDay();

        $nombre = Activity::where('created_at', '<', $limite)->delete();

        activity()
            ->event('purge')
            ->withProperties(['jours_conserves' => $jours, 'lignes_supprimees' => $nombre])
            ->log("Purge du journal d'audit : {$nombre} entrée(s) antérieure(s) au {$limite->format('d/m/Y')} supprimée(s).");

        return $nombre;
    }

    public static function libelleModele(?string $classe): string
    {
        if (! $classe) {
            return '—';
        }

        $nomCourt = class_basename($classe);

        return self::LIBELLES_MODELES[$nomCourt] ?? Str::headline($nomCourt);
    }

    /**
     * @return array<string, string> classe => libellé, pour le filtre "Élément".
     */
    public static function modelesAudites(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $classe) => [$classe => self::libelleModele($classe)])
            ->sort()
            ->all();
    }

    private function doitEtreAudite(Model $modele): bool
    {
        if ($modele instanceof Activity || in_array($modele::class, self::MODELES_EXCLUS, true)) {
            return false;
        }

        // Marqueurs techniques (ex. date de dernière réinitialisation des
        // statuts) : pas des réglages modifiés par un utilisateur.
        return ! ($modele instanceof Parametre && $modele->groupe === 'interne');
    }

    /**
     * @return array{old: array<string, mixed>, attributes: array<string, mixed>}
     */
    private function modifications(Model $modele): array
    {
        $nouvelles = $this->filtrer($modele, $modele->getChanges());

        $anciennes = [];
        foreach (array_keys($nouvelles) as $attribut) {
            $anciennes[$attribut] = $modele->getRawOriginal($attribut);
        }

        return ['old' => $anciennes, 'attributes' => $nouvelles];
    }

    /**
     * @param  array<string, mixed>  $attributs
     * @return array<string, mixed>
     */
    private function filtrer(Model $modele, array $attributs): array
    {
        return array_diff_key(
            $attributs,
            array_flip([...self::ATTRIBUTS_IGNORES, ...$modele->getHidden()]),
        );
    }

    private function description(string $evenement, Model $modele): string
    {
        $archivage = $evenement === 'deleted'
            && method_exists($modele, 'isForceDeleting')
            && ! $modele->isForceDeleting();

        $action = match (true) {
            $evenement === 'created' => 'créé(e)',
            $evenement === 'updated' => 'modifié(e)',
            $archivage => 'archivé(e)',
            $evenement === 'deleted' => 'supprimé(e)',
            $evenement === 'restored' => 'restauré(e)',
            default => $evenement,
        };

        return self::libelleModele($modele::class)." « {$this->identifiant($modele)} » {$action}.";
    }

    private function identifiant(Model $modele): string
    {
        $attributs = $modele->getAttributes();

        foreach (self::ATTRIBUTS_IDENTIFIANTS as $attribut) {
            $valeur = $attributs[$attribut] ?? null;

            if (is_scalar($valeur) && $valeur !== '') {
                return (string) $valeur;
            }
        }

        return '#'.$modele->getKey();
    }
}
