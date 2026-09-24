<x-app-layout>
    <x-slot name="header">Tableau de bord</x-slot>

    <p class="text-muted small mb-3">
        Bonjour, {{ Auth::user()->name }} — situation au {{ now()->format('d/m/Y à H:i') }}.
    </p>

    {{-- Parc & recette du jour : vision globale si accordée, sinon bornée aux
    véhicules attribués (jamais les deux à la fois). --}}
    @can('flotte.vehicule.voir')
        <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Parc & recette du jour</h2>
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-3">
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Véhicules en circulation</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ $parc['disponibles'] }} / {{ $parc['total'] }}</div>
                        <div class="small {{ $parc['taux_disponibilite'] >= 70 ? 'text-success' : 'text-danger' }}">{{ $parc['taux_disponibilite'] }} % de disponibilité</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Recette attendue aujourd'hui</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($recetteDuJour['attendu']) }} FCFA</div>
                        <div class="small text-muted">{{ $recetteDuJour['taux'] }} % déjà versé</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Déjà versé aujourd'hui</div>
                        <div class="h5 mb-0 text-success">{{ \App\Support\Money::format($recetteDuJour['verse']) }} FCFA</div>
                    </div>
                </div>
            </div>
        </div>
    @elsecan('flotte.vehicule.voir_affectes')
        <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Mon parc</h2>
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Mes véhicules en circulation</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ $parcGestionnaire['disponibles'] }} / {{ $parcGestionnaire['total'] }}</div>
                        <div class="small {{ $parcGestionnaire['taux_disponibilite'] >= 70 ? 'text-success' : 'text-danger' }}">{{ $parcGestionnaire['taux_disponibilite'] }} % de disponibilité</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Recette attendue aujourd'hui</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($recetteDuJourGestionnaire['attendu']) }} FCFA</div>
                        <div class="small text-muted">{{ $recetteDuJourGestionnaire['taux'] }} % déjà versé</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Déjà versé aujourd'hui</div>
                        <div class="h5 mb-0 text-success">{{ \App\Support\Money::format($recetteDuJourGestionnaire['verse']) }} FCFA</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Ma dette</div>
                        <div class="h5 mb-0 {{ $detteGestionnaire > 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Money::format($detteGestionnaire) }} FCFA</div>
                    </div>
                </div>
            </div>
        </div>
        @if ($parcGestionnaire['total'] === 0)
            <p class="text-muted small mt-n3 mb-4">Aucun véhicule ne vous est attribué pour le moment.</p>
        @endif
    @endcan

    {{-- Stock & achats --}}
    @can('stock.tableau_bord.voir')
        <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Stock & achats</h2>
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Valeur du stock</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($stock['valeur_totale']) }} FCFA</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Articles en alerte</div>
                        <div class="h5 mb-0 {{ $stock['nombre_alertes'] > 0 ? 'text-danger' : 'text-success' }}">{{ $stock['nombre_alertes'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Dû aux fournisseurs</div>
                        <div class="h5 mb-0 {{ $stock['du_fournisseurs'] > 0 ? 'text-danger' : '' }}">{{ \App\Support\Money::format($stock['du_fournisseurs']) }} FCFA</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Achats / Ventes (mois)</div>
                        <div class="h6 mb-0"><span class="text-danger">{{ \App\Support\Money::format($achatsVentesDuMois['achats']) }}</span> / <span class="text-success">{{ \App\Support\Money::format($achatsVentesDuMois['ventes']) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
        @if (count($stock['articles_en_alerte']) > 0)
            <div class="card shadow-sm border-0 bg-white mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Articles en alerte</h2>
                    <a href="{{ route('stock.etat-stock.index') }}" class="small">Voir tout</a>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($stock['articles_en_alerte'] as $article)
                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                            <span>{{ $article->nom }}</span>
                            <strong class="text-danger">{{ $article->quantite_stock }} / {{ $article->seuil_alerte }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endcan

    {{-- Opérations programmées --}}
    @can('operations.voir')
        <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Opérations programmées</h2>
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">En retard</div>
                        <div class="h5 mb-0 {{ $operations['operations_en_retard'] > 0 ? 'text-danger' : '' }}">{{ $operations['operations_en_retard'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">À venir</div>
                        <div class="h5 mb-0 {{ $operations['operations_a_venir'] > 0 ? 'text-warning' : '' }}">{{ $operations['operations_a_venir'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col col-lg-2">
                <a href="{{ route('flotte.operations.index') }}" class="card shadow-sm border-0 bg-white h-100 text-decoration-none d-flex align-items-center justify-content-center">
                    <div class="card-body text-center">
                        <i class="bi bi-tools fs-4" style="color: var(--al-navy);"></i>
                        <div class="small mt-1">Opérations</div>
                    </div>
                </a>
            </div>
        </div>
    @endcan

    {{-- Entretien / interventions --}}
    @can('interventions.voir')
        <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Entretien</h2>
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-3">
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Interventions en cours</div>
                        <div class="h5 mb-0 {{ $interventions['en_cours'] > 0 ? 'text-warning' : '' }}">{{ $interventions['en_cours'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Interventions ce mois-ci</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ $interventions['ce_mois'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col col-lg-1">
                <a href="{{ route('flotte.interventions.index') }}" class="card shadow-sm border-0 bg-white h-100 text-decoration-none d-flex align-items-center justify-content-center">
                    <div class="card-body text-center">
                        <i class="bi bi-exclamation-triangle fs-4" style="color: var(--al-navy);"></i>
                        <div class="small mt-1">Interventions</div>
                    </div>
                </a>
            </div>
        </div>
        @if (count($interventions['liste_en_cours']) > 0)
            <div class="card shadow-sm border-0 bg-white mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Pannes en cours</h2>
                    <a href="{{ route('flotte.interventions.index') }}" class="small">Voir tout</a>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($interventions['liste_en_cours'] as $intervention)
                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                            <span><strong>{{ $intervention->vehicule_code }}</strong> — {{ $intervention->type_panne_libelle ?? 'Type non précisé' }}</span>
                            <span class="text-muted">depuis le {{ $intervention->date_debut->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endcan

    {{-- Prêts & financements --}}
    @can('financements.voir')
        <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Prêts & financements</h2>
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">Reste à rembourser</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($financements['restant_du']) }} FCFA</div>
                        <div class="small text-muted">{{ $financements['nombre_en_cours'] }} financement(s) en cours</div>
                    </div>
                </div>
            </div>
            <div class="col col-lg-2">
                <a href="{{ route('financements.financements.index') }}" class="card shadow-sm border-0 bg-white h-100 text-decoration-none d-flex align-items-center justify-content-center">
                    <div class="card-body text-center">
                        <i class="bi bi-cash-stack fs-4" style="color: var(--al-navy);"></i>
                        <div class="small mt-1">Financements</div>
                    </div>
                </a>
            </div>
        </div>
    @endcan
</x-app-layout>
