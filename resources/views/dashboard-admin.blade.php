<x-app-layout>
    <x-slot name="header">Tableau de bord</x-slot>

    <p class="text-muted small mb-3">
        Bonjour, {{ Auth::user()->name }} — situation au {{ now()->format('d/m/Y à H:i') }}.
    </p>

    {{-- Parc & recette du jour --}}
    <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Parc & recette du jour</h2>
    <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
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
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Dette globale gestionnaires</div>
                    <div class="h5 mb-0 {{ $dette['total'] > 0 ? 'text-danger' : '' }}">{{ \App\Support\Money::format($dette['total']) }} FCFA</div>
                    <div class="small text-muted">{{ $dette['nombre_gestionnaires'] }} gestionnaire(s) concerné(s)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stock & achats --}}
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

    {{-- Entretien & financements --}}
    <h2 class="h6 text-uppercase small fw-semibold mb-2" style="color: var(--al-navy); letter-spacing: .04em;">Entretien & financements</h2>
    <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Interventions en cours</div>
                    <div class="h5 mb-0 {{ $entretien['interventions_en_cours'] > 0 ? 'text-warning' : '' }}">{{ $entretien['interventions_en_cours'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Opérations en retard</div>
                    <div class="h5 mb-0 {{ $entretien['operations_en_retard'] > 0 ? 'text-danger' : '' }}">{{ $entretien['operations_en_retard'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Opérations à venir</div>
                    <div class="h5 mb-0 {{ $entretien['operations_a_venir'] > 0 ? 'text-warning' : '' }}">{{ $entretien['operations_a_venir'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Prêts en cours — reste dû</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($financements['restant_du']) }} FCFA</div>
                    <div class="small text-muted">{{ $financements['nombre_en_cours'] }} financement(s) en cours</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Graphiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Parc par statut</h2></div>
                <div class="card-body">
                    <canvas id="graphique-parc" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Versements — 30 derniers jours</h2></div>
                <div class="card-body">
                    <canvas id="graphique-versements" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Soldes des caisses</h2></div>
                <div class="card-body">
                    <canvas id="graphique-caisses" height="90"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Listes courtes --}}
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Gestionnaires les plus endettés</h2>
                    @can('flotte.dette.gerer')
                        <a href="{{ route('flotte.dettes.index') }}" class="small">Voir tout</a>
                    @endcan
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($dette['top'] as $gestionnaire)
                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                            <span>{{ $gestionnaire->name }}</span>
                            <strong class="text-danger">{{ \App\Support\Money::format($gestionnaire->dette) }} FCFA</strong>
                        </li>
                    @empty
                        <li class="list-group-item small text-muted">Aucune dette en cours.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Articles en alerte</h2>
                    @can('stock.tableau_bord.voir')
                        <a href="{{ route('stock.etat-stock.index') }}" class="small">Voir tout</a>
                    @endcan
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($stock['articles_en_alerte'] as $article)
                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                            <span>{{ $article->nom }}</span>
                            <strong class="text-danger">{{ $article->quantite_stock }} / {{ $article->seuil_alerte }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item small text-muted">Aucun article sous le seuil.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Financements en cours</h2>
                    @can('financements.voir')
                        <a href="{{ route('financements.financements.index') }}" class="small">Voir tout</a>
                    @endcan
                </div>
                <div class="card-body small text-muted">
                    {{ $financements['nombre_en_cours'] }} emprunt(s) actif(s), {{ \App\Support\Money::format($financements['restant_du']) }} FCFA restant à rembourser au total.
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const couleurNavy = '#073763';
            const couleurs = ['#073763', '#f0ad4e', '#dc3545', '#28a745', '#6c757d'];

            new Chart(document.getElementById('graphique-parc'), {
                type: 'doughnut',
                data: {
                    labels: @json(collect($parc['repartition'])->pluck('libelle')->values()),
                    datasets: [{
                        data: @json(collect($parc['repartition'])->pluck('count')->values()),
                        backgroundColor: couleurs,
                    }],
                },
                options: {
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                },
            });

            new Chart(document.getElementById('graphique-versements'), {
                type: 'line',
                data: {
                    labels: @json($versements30j['labels']),
                    datasets: [{
                        label: 'Versements (FCFA)',
                        data: @json($versements30j['montants']),
                        borderColor: couleurNavy,
                        backgroundColor: 'rgba(7, 55, 99, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 2,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } },
                },
            });

            new Chart(document.getElementById('graphique-caisses'), {
                type: 'bar',
                data: {
                    labels: @json(collect($caisses)->pluck('libelle')),
                    datasets: [{
                        label: 'Solde (FCFA)',
                        data: @json(collect($caisses)->pluck('solde')),
                        backgroundColor: couleurNavy,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } },
                },
            });
        });
        </script>
    @endpush
</x-app-layout>
