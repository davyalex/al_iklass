@php
    $badgesStatut = \App\Support\StatutVehiculeBadges::classes();
@endphp

<x-app-layout>
    <x-slot name="header">Rapport véhicule — {{ $vehicule->code }}</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <a href="{{ route('flotte.vehicules.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux véhicules
        </a>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <h2 class="h5 mb-0">{{ $vehicule->code }} — {{ $vehicule->libelle }}</h2>
                <span class="badge {{ $badgesStatut[$vehicule->statut?->code] ?? 'bg-secondary' }}">{{ $vehicule->statut?->libelle ?? 'Sans statut' }}</span>
            </div>
            <dl class="row mb-0 small">
                <dt class="col-6 col-md-3">Marque / Modèle</dt>
                <dd class="col-6 col-md-3">{{ trim(($vehicule->marque ?? '').' '.($vehicule->modele ?? '')) ?: '—' }}</dd>
                <dt class="col-6 col-md-3">Immatriculation</dt>
                <dd class="col-6 col-md-3">{{ $vehicule->immatriculation ?? '—' }}</dd>
                <dt class="col-6 col-md-3">Chauffeur</dt>
                <dd class="col-6 col-md-3">{{ trim(($vehicule->chauffeur_nom ?? '').' '.($vehicule->chauffeur_telephone ? '— '.$vehicule->chauffeur_telephone : '')) ?: '—' }}</dd>
                <dt class="col-6 col-md-3">Gestionnaire</dt>
                <dd class="col-6 col-md-3">{{ $vehicule->gestionnaire?->name ?? 'Non affecté' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-3 mb-3 row-cols-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Changements de statut</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['changements_statut'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Sorties de pièces</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['sorties_pieces'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Dépannages traités</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['depannages_traites'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($rapportsIntervention->isNotEmpty())
        <div class="card shadow-sm border-0 bg-white mb-3">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h6 text-uppercase text-muted mb-0">Rapports d'intervention</h2>
            </div>
            <div class="card-body pt-2">
                @foreach ($rapportsIntervention as $rapport)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                            <div class="fw-semibold small">{{ $rapport->ancien_statut_libelle ?? 'Création' }} → {{ $rapport->nouveau_statut_libelle }}</div>
                            <div class="text-muted small text-nowrap">{{ $rapport->created_at->format('d/m/Y H:i') }} — {{ $rapport->user?->name ?? '—' }}</div>
                        </div>
                        <div class="small mt-1">{{ $rapport->commentaire }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-rapport" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-rapport">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-rapport">
                        Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white border-0 pt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="onglet-rapport-statuts" data-bs-toggle="tab" data-bs-target="#tab-rapport-statuts" type="button">Changements de statut</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="onglet-rapport-sorties" data-bs-toggle="tab" data-bs-target="#tab-rapport-sorties" type="button">Sorties de pièces</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="onglet-rapport-interventions" data-bs-toggle="tab" data-bs-target="#tab-rapport-interventions" type="button">Interventions</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="onglet-rapport-operations" data-bs-toggle="tab" data-bs-target="#tab-rapport-operations" type="button">Opérations programmées</button>
                </li>
            </ul>
            <div>
                <div class="onglet-export" data-onglet="statuts"><x-export-dropdown id-suffix="rapport-statuts" /></div>
                <div class="onglet-export d-none" data-onglet="sorties"><x-export-dropdown id-suffix="rapport-sorties" /></div>
                <div class="onglet-export d-none" data-onglet="interventions"><x-export-dropdown id-suffix="rapport-interventions" /></div>
                <div class="onglet-export d-none" data-onglet="operations"><x-export-dropdown id-suffix="rapport-operations" /></div>
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-rapport-statuts">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-rapport-statuts">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transition</th>
                                    <th>Auteur</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-rapport-sorties">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-rapport-sorties">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th class="text-end">Articles</th>
                                    <th class="text-end">Quantité</th>
                                    <th>Motif</th>
                                    <th>Auteur</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-rapport-interventions">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-rapport-interventions">
                            <thead>
                                <tr>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Type de panne</th>
                                    <th>Description</th>
                                    <th>Rapport</th>
                                    <th>Clôturée par</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-rapport-operations">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-rapport-operations">
                            <thead>
                                <tr>
                                    <th>Date de réalisation</th>
                                    <th>Type</th>
                                    <th>Échéance prévue</th>
                                    <th>Réalisé par</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            function filtres() {
                return {
                    date_debut: $('#filtres-rapport [name=date_debut]').val(),
                    date_fin: $('#filtres-rapport [name=date_fin]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtres()).some((v) => !!v);
                $('#btn-reset-rapport').toggleClass('d-none', !actif);
            }

            $('#filtres-rapport').on('change input', actualiserBoutonReset);

            const tableStatuts = $('#table-rapport-statuts').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.vehicules.rapport.statuts', $vehicule) }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'transition', name: 'transition', orderable: false },
                    { data: 'auteur', name: 'auteur', orderable: false },
                    { data: 'commentaire', name: 'commentaire', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            const tableSorties = $('#table-rapport-sorties').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.vehicules.rapport.sorties', $vehicule) }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_sortie', name: 'date_sortie' },
                    { data: 'reference', name: 'reference' },
                    { data: 'lignes_count', name: 'lignes_count', className: 'text-end', orderable: false },
                    { data: 'quantite_totale', name: 'quantite_totale', className: 'text-end', orderable: false },
                    { data: 'motif', name: 'motif' },
                    { data: 'auteur', name: 'auteur', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            const tableInterventions = $('#table-rapport-interventions').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.vehicules.rapport.interventions', $vehicule) }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_debut', name: 'date_debut' },
                    { data: 'date_fin', name: 'date_fin' },
                    { data: 'type_panne_libelle', name: 'type_panne_libelle', orderable: false },
                    { data: 'description', name: 'description', orderable: false },
                    { data: 'rapport', name: 'rapport', orderable: false },
                    { data: 'cloturee_par', name: 'clotureePar.name', orderable: false },
                ],
                order: [[1, 'desc']],
            });

            const tableOperations = $('#table-rapport-operations').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.vehicules.rapport.operations', $vehicule) }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_realisation', name: 'date_realisation' },
                    { data: 'type_operation_libelle', name: 'type_operation_libelle', orderable: false },
                    { data: 'date_echeance', name: 'date_echeance' },
                    { data: 'realise_par', name: 'realisePar.name', orderable: false },
                    { data: 'commentaire', name: 'commentaire', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            const tablesParOnglet = {
                statuts: tableStatuts,
                sorties: tableSorties,
                interventions: tableInterventions,
                operations: tableOperations,
            };

            $('#onglet-rapport-sorties').on('shown.bs.tab', () => tableSorties.columns.adjust());
            $('#onglet-rapport-interventions').on('shown.bs.tab', () => tableInterventions.columns.adjust());
            $('#onglet-rapport-operations').on('shown.bs.tab', () => tableOperations.columns.adjust());

            // Le bouton d'export affiché correspond toujours à l'onglet actif.
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const onglet = $(e.target).attr('id').replace('onglet-rapport-', '');
                $('.onglet-export').addClass('d-none');
                $(`.onglet-export[data-onglet="${onglet}"]`).removeClass('d-none');
            });

            $('#btn-filtrer-rapport').on('click', function () {
                Object.values(tablesParOnglet).forEach((table) => table.ajax.reload());
            });

            $('#btn-reset-rapport').on('click', function () {
                $('#filtres-rapport')[0].reset();
                Object.values(tablesParOnglet).forEach((table) => table.ajax.reload());
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtres());
                return base + '?' + params.toString();
            }

            const exportRoutes = {
                statuts: {
                    excel: '{{ route('flotte.vehicules.rapport.export.statuts.excel', $vehicule) }}',
                    pdf: '{{ route('flotte.vehicules.rapport.export.statuts.pdf', $vehicule) }}',
                },
                sorties: {
                    excel: '{{ route('flotte.vehicules.rapport.export.sorties.excel', $vehicule) }}',
                    pdf: '{{ route('flotte.vehicules.rapport.export.sorties.pdf', $vehicule) }}',
                },
                interventions: {
                    excel: '{{ route('flotte.vehicules.rapport.export.interventions.excel', $vehicule) }}',
                    pdf: '{{ route('flotte.vehicules.rapport.export.interventions.pdf', $vehicule) }}',
                },
                operations: {
                    excel: '{{ route('flotte.vehicules.rapport.export.operations.excel', $vehicule) }}',
                    pdf: '{{ route('flotte.vehicules.rapport.export.operations.pdf', $vehicule) }}',
                },
            };

            Object.keys(exportRoutes).forEach(function (onglet) {
                $(`#btn-export-excel-rapport-${onglet}`).on('click', function (e) {
                    e.preventDefault();
                    window.location = urlAvecFiltres(exportRoutes[onglet].excel);
                });
                $(`#btn-export-pdf-rapport-${onglet}`).on('click', function (e) {
                    e.preventDefault();
                    window.location = urlAvecFiltres(exportRoutes[onglet].pdf);
                });
            });
        });
        </script>
    @endpush
</x-app-layout>
