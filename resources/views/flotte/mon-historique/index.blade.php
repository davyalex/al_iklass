<x-app-layout>
    <x-slot name="header">Mon historique</x-slot>

    <div class="row g-3 mb-3 row-cols-2 row-cols-lg-4">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Dépannages traités (mois)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-depannages-mois">0</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Sorties de pièces (mois)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-pieces-sorties-mois">0</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-mon-historique" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-mon-historique">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-mon-historique">
                        Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white border-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-mes-statuts" type="button">Changements de statut</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="onglet-mes-sorties" data-bs-toggle="tab" data-bs-target="#tab-mes-sorties" type="button">Sorties de pièces</button>
                </li>
            </ul>
        </div>
        <div class="card-body pt-3">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-mes-statuts">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-mes-statuts">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Véhicule</th>
                                    <th>Transition</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-mes-sorties">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-mes-sorties">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Véhicule</th>
                                    <th class="text-end">Articles</th>
                                    <th class="text-end">Quantité</th>
                                    <th>Motif</th>
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
                    date_debut: $('#filtres-mon-historique [name=date_debut]').val(),
                    date_fin: $('#filtres-mon-historique [name=date_fin]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtres()).some((v) => !!v);
                $('#btn-reset-mon-historique').toggleClass('d-none', !actif);
            }

            $('#filtres-mon-historique').on('change input', actualiserBoutonReset);

            const tableStatuts = $('#table-mes-statuts').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.mon-historique.statuts') }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'vehicule_code', name: 'vehicule_code' },
                    { data: 'transition', name: 'transition', orderable: false },
                    { data: 'commentaire', name: 'commentaire', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            const tableSorties = $('#table-mes-sorties').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.mon-historique.sorties') }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_sortie', name: 'date_sortie' },
                    { data: 'reference', name: 'reference' },
                    { data: 'vehicule_code', name: 'vehicule_code' },
                    { data: 'lignes_count', name: 'lignes_count', className: 'text-end', orderable: false },
                    { data: 'quantite_totale', name: 'quantite_totale', className: 'text-end', orderable: false },
                    { data: 'motif', name: 'motif' },
                ],
                order: [[0, 'desc']],
            });

            $('#onglet-mes-sorties').on('shown.bs.tab', () => tableSorties.columns.adjust());

            function rafraichirKpis() {
                $.get('{{ route('flotte.mon-historique.kpis') }}', function (kpis) {
                    $('#kpi-depannages-mois').text(kpis.depannages_mois);
                    $('#kpi-pieces-sorties-mois').text(kpis.pieces_sorties_mois);
                });
            }

            rafraichirKpis();

            $('#btn-filtrer-mon-historique').on('click', function () {
                tableStatuts.ajax.reload();
                tableSorties.ajax.reload();
            });

            $('#btn-reset-mon-historique').on('click', function () {
                $('#filtres-mon-historique')[0].reset();
                tableStatuts.ajax.reload();
                tableSorties.ajax.reload();
            });
        });
        </script>
    @endpush
</x-app-layout>
