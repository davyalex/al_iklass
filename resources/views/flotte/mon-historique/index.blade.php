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

            function rafraichirKpis() {
                $.get('{{ route('flotte.mon-historique.kpis') }}', function (kpis) {
                    $('#kpi-depannages-mois').text(kpis.depannages_mois);
                });
            }

            rafraichirKpis();

            $('#btn-filtrer-mon-historique').on('click', function () {
                tableStatuts.ajax.reload();
            });

            $('#btn-reset-mon-historique').on('click', function () {
                $('#filtres-mon-historique')[0].reset();
                tableStatuts.ajax.reload();
            });
        });
        </script>
    @endpush
</x-app-layout>
