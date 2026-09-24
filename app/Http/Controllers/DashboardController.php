<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\Article;
use App\Models\Caisse;
use App\Models\Financement;
use App\Models\Intervention;
use App\Models\MouvementCaisse;
use App\Models\OperationProgrammee;
use App\Models\SortieStock;
use App\Models\StatutVehicule;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Versement;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Tableau de bord : contenu déterminé par permission, jamais par le nom du
 * rôle (cf. CLAUDE.md §4 — "vérifier la permission, pas seulement le rôle").
 *
 * - caisse.voir (admin/superadmin) → vue consolidée unique de tous les
 *   modules (dashboard-admin), inchangée.
 * - Sinon → vue modulaire (dashboard-home) : CHAQUE section s'affiche
 *   indépendamment selon sa propre permission (stock.tableau_bord.voir,
 *   flotte.vehicule.voir_affectes, operations.voir, interventions.voir,
 *   financements.voir...). Un rôle par défaut n'en a qu'une ou deux ; si un
 *   admin lui accorde une permission supplémentaire depuis Admin > Rôles, la
 *   section correspondante apparaît automatiquement au prochain chargement
 *   — jamais besoin de modifier ce contrôleur pour ça.
 * - Aucune des permissions ci-dessus → accueil simple (dashboard).
 *
 * Chaque module détaillé reste de toute façon accessible depuis son propre
 * menu, gated par sa propre permission (cf. sidebar-nav.blade.php) — ce
 * contrôleur ne fait qu'agréger un résumé de ce que l'utilisateur peut déjà
 * voir ailleurs.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $utilisateur = auth()->user();

        if ($utilisateur->can('caisse.voir')) {
            return view('dashboard-admin', [
                'parc' => $this->kpisParc(),
                'recetteDuJour' => $this->kpisRecetteDuJour(),
                'dette' => $this->kpisDette(),
                'versements30j' => $this->versementsTrenteDerniersJours(),
                'stock' => $this->kpisStock(),
                'achatsVentesDuMois' => $this->achatsVentesDuMois(),
                'entretien' => $this->kpisEntretien(),
                'caisses' => $this->soldesCaisses(),
                'financements' => $this->kpisFinancements(),
            ]);
        }

        $data = [];

        // Vision globale du parc (permission élargie hors caisse.voir) prime
        // sur la vision bornée "mes véhicules attribués" pour éviter
        // d'afficher les deux sections en double si les deux sont accordées.
        if ($utilisateur->can('flotte.vehicule.voir')) {
            $data['parc'] = $this->kpisParc();
            $data['recetteDuJour'] = $this->kpisRecetteDuJour();
        } elseif ($utilisateur->can('flotte.vehicule.voir_affectes')) {
            $data['parcGestionnaire'] = $this->kpisParcGestionnaire($utilisateur);
            $data['recetteDuJourGestionnaire'] = $this->kpisRecetteDuJourGestionnaire($utilisateur);
            $data['detteGestionnaire'] = (float) $utilisateur->dette;
        }

        if ($utilisateur->can('stock.tableau_bord.voir')) {
            $data['stock'] = $this->kpisStock();
            $data['achatsVentesDuMois'] = $this->achatsVentesDuMois();
        }

        if ($utilisateur->can('operations.voir')) {
            $data['operations'] = $this->kpisOperationsProgrammees();
        }

        if ($utilisateur->can('interventions.voir')) {
            $data['interventions'] = $this->kpisInterventionsMecanicien();
        }

        if ($utilisateur->can('financements.voir')) {
            $data['financements'] = $this->kpisFinancements();
        }

        if ($data === []) {
            return view('dashboard');
        }

        return view('dashboard-home', $data);
    }

    /**
     * @return array{total: int, disponibles: int, taux_disponibilite: float, repartition: array<string, array{libelle: string, count: int}>}
     */
    private function kpisParc(): array
    {
        // "actif" sur Vehicule est piloté par VehiculeObserver et reflète en
        // réalité "en circulation" (pas "non archivé") : on dérive donc la
        // disponibilité depuis le statut, jamais depuis "actif". Agrégats
        // SQL (pas de get()->filter() en PHP) pour rester O(1) requêtes
        // quel que soit le nombre de véhicules.
        $total = Vehicule::count();

        $totalParStatut = Vehicule::selectRaw('statut_id, COUNT(*) as total')
            ->groupBy('statut_id')
            ->pluck('total', 'statut_id');

        $statuts = StatutVehicule::where('actif', true)->orderBy('id')->get();

        $disponibles = (int) ($totalParStatut[$statuts->firstWhere('code', 'en_circulation')?->id] ?? 0);

        $repartition = $statuts->mapWithKeys(fn (StatutVehicule $statut) => [
            $statut->code => [
                'libelle' => $statut->libelle,
                'count' => (int) ($totalParStatut[$statut->id] ?? 0),
            ],
        ])->all();

        return [
            'total' => $total,
            'disponibles' => $disponibles,
            'taux_disponibilite' => $total > 0 ? round($disponibles / $total * 100) : 0,
            'repartition' => $repartition,
        ];
    }

    /**
     * @return array{attendu: float, verse: float, taux: float}
     */
    private function kpisRecetteDuJour(): array
    {
        $attendu = (float) Vehicule::whereHas('statut', fn ($q) => $q->where('code', 'en_circulation'))
            ->sum('recette_journaliere');

        $verse = (float) Versement::whereDate('date_versement', now())->sum('montant');

        return [
            'attendu' => $attendu,
            'verse' => $verse,
            'taux' => $attendu > 0 ? round(min($verse, $attendu) / $attendu * 100) : 100,
        ];
    }

    /**
     * @return array{total: float, nombre_gestionnaires: int, top: Collection<int, User>}
     */
    private function kpisDette(): array
    {
        $enDette = User::role('gestionnaire')->where('dette', '>', 0)->orderByDesc('dette')->get();

        return [
            'total' => (float) $enDette->sum('dette'),
            'nombre_gestionnaires' => $enDette->count(),
            'top' => $enDette->take(5),
        ];
    }

    /**
     * @return array{labels: list<string>, montants: list<float>}
     */
    private function versementsTrenteDerniersJours(): array
    {
        $debut = now()->subDays(29)->startOfDay();

        $parJour = Versement::where('date_versement', '>=', $debut)
            ->selectRaw('DATE(date_versement) as jour, SUM(montant) as total')
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $labels = [];
        $montants = [];

        for ($jour = $debut->copy(); $jour->lte(now()); $jour->addDay()) {
            $cle = $jour->format('Y-m-d');
            $labels[] = $jour->format('d/m');
            $montants[] = (float) ($parJour[$cle] ?? 0);
        }

        return ['labels' => $labels, 'montants' => $montants];
    }

    /**
     * @return array{valeur_totale: float, articles_en_alerte: Collection<int, Article>}
     */
    private function kpisStock(): array
    {
        // Agrégats et filtre "en alerte" poussés en SQL (whereColumn) plutôt
        // que de charger tous les articles en mémoire pour les filtrer en PHP.
        $valeurTotale = (float) Article::where('actif', true)
            ->selectRaw('COALESCE(SUM(quantite_stock * prix_achat), 0) as valeur')
            ->value('valeur');

        $articlesEnAlerteQuery = Article::where('actif', true)->whereColumn('quantite_stock', '<=', 'seuil_alerte');

        return [
            'valeur_totale' => $valeurTotale,
            'articles_en_alerte' => (clone $articlesEnAlerteQuery)->orderBy('quantite_stock')->limit(5)->get(),
            'nombre_alertes' => (clone $articlesEnAlerteQuery)->count(),
            'du_fournisseurs' => (float) Achat::sum('montant_restant'),
        ];
    }

    /**
     * @return array{achats: float, ventes: float}
     */
    private function achatsVentesDuMois(): array
    {
        return [
            'achats' => (float) Achat::duMois(now())->sum('montant_total'),
            'ventes' => (float) SortieStock::externe()->duMois(now())->sum('montant_total'),
        ];
    }

    /**
     * @return array{interventions_en_cours: int, operations_en_retard: int, operations_a_venir: int}
     */
    private function kpisEntretien(): array
    {
        return [
            'interventions_en_cours' => Intervention::where('statut', 'en_cours')->count(),
            ...$this->kpisOperationsProgrammees(),
        ];
    }

    /**
     * @return array{operations_en_retard: int, operations_a_venir: int}
     */
    private function kpisOperationsProgrammees(): array
    {
        // Chargé en mémoire (borné : seules les opérations encore "planifiee",
        // jamais tout l'historique) pour réutiliser OperationProgrammee::badge()
        // comme source unique de vérité sur les seuils retard/à venir, plutôt
        // que de dupliquer cette logique de dates en SQL brut.
        $badges = OperationProgrammee::where('statut', 'planifiee')->get()->map->badge();

        return [
            'operations_en_retard' => $badges->filter(fn (?string $b) => in_array($b, ['depasse', 'jour_j'], true))->count(),
            'operations_a_venir' => $badges->filter(fn (?string $b) => $b === 'a_venir')->count(),
        ];
    }

    /**
     * Parc borné aux véhicules attribués à ce gestionnaire — jamais celui de
     * toute l'entreprise (cf. flotte.vehicule.voir_affectes).
     *
     * @return array{total: int, disponibles: int, taux_disponibilite: float, repartition: array<string, array{libelle: string, count: int}>}
     */
    private function kpisParcGestionnaire(User $gestionnaire): array
    {
        $vehicules = Vehicule::where('gestionnaire_id', $gestionnaire->id)->with('statut')->get();
        $total = $vehicules->count();
        $disponibles = $vehicules->filter(fn (Vehicule $v) => $v->statut?->code === 'en_circulation')->count();

        $repartition = StatutVehicule::where('actif', true)->orderBy('id')->get()
            ->mapWithKeys(fn (StatutVehicule $statut) => [
                $statut->code => [
                    'libelle' => $statut->libelle,
                    'count' => $vehicules->where('statut_id', $statut->id)->count(),
                ],
            ])->all();

        return [
            'total' => $total,
            'disponibles' => $disponibles,
            'taux_disponibilite' => $total > 0 ? round($disponibles / $total * 100) : 0,
            'repartition' => $repartition,
        ];
    }

    /**
     * @return array{attendu: float, verse: float, taux: float}
     */
    private function kpisRecetteDuJourGestionnaire(User $gestionnaire): array
    {
        $attendu = (float) Vehicule::where('gestionnaire_id', $gestionnaire->id)
            ->whereHas('statut', fn ($q) => $q->where('code', 'en_circulation'))
            ->sum('recette_journaliere');

        $verse = (float) Versement::where('gestionnaire_id', $gestionnaire->id)
            ->whereDate('date_versement', now())
            ->sum('montant');

        return [
            'attendu' => $attendu,
            'verse' => $verse,
            'taux' => $attendu > 0 ? round(min($verse, $attendu) / $attendu * 100) : 100,
        ];
    }

    /**
     * @return array{en_cours: int, ce_mois: int, liste_en_cours: Collection<int, Intervention>}
     */
    private function kpisInterventionsMecanicien(): array
    {
        $debutMois = now()->startOfMonth();
        $finMois = now()->endOfMonth();

        return [
            'en_cours' => Intervention::where('statut', 'en_cours')->count(),
            'ce_mois' => Intervention::whereBetween('date_debut', [$debutMois, $finMois])->count(),
            'liste_en_cours' => Intervention::where('statut', 'en_cours')
                ->orderByDesc('date_debut')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return list<array{libelle: string, solde: float}>
     */
    private function soldesCaisses(): array
    {
        $soldesParCaisse = MouvementCaisse::selectRaw("caisse_id, SUM(CASE WHEN sens = 'entree' THEN montant ELSE -montant END) as solde")
            ->groupBy('caisse_id')
            ->pluck('solde', 'caisse_id');

        return Caisse::where('actif', true)->orderBy('libelle')->get()
            ->map(fn (Caisse $caisse) => [
                'libelle' => $caisse->libelle,
                'solde' => (float) ($soldesParCaisse[$caisse->id] ?? 0),
            ])->all();
    }

    /**
     * @return array{restant_du: float, nombre_en_cours: int}
     */
    private function kpisFinancements(): array
    {
        return [
            'restant_du' => (float) Financement::where('statut', 'en_cours')->sum('montant_restant'),
            'nombre_en_cours' => Financement::where('statut', 'en_cours')->count(),
        ];
    }
}
