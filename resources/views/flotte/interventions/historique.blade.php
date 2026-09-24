<x-app-layout>
    <x-slot name="header">Historique des interventions</x-slot>

    <div class="al-page-actions">
        <a href="{{ route('flotte.interventions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux interventions
        </a>
    </div>

    {{-- KPI --}}
    <div class="row g-2 g-sm-3 mb-3 row-cols-2 row-cols-sm-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">En cours</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-en-cours">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Ce mois-ci</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-ce-mois">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Sur la période</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-periode">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-historique-interventions" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Véhicule</label>
                    <select name="vehicule_id" class="form-select form-select-sm select2-filtre-historique-vehicule">
                        <option value="">Tous les véhicules</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->id }}" @selected(request('vehicule_id') == $vehicule->id)>{{ $vehicule->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type_panne_id" class="form-select form-select-sm">
                        <option value="">Tous les types</option>
                        @foreach ($typesPanne as $type)
                            <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md al-filtres-actions justify-content-md-end">
                    <x-filtre-reset id="btn-reset-historique-interventions" />
                    <x-export-dropdown id-suffix="historique-interventions" />
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-historique-interventions">
                <thead>
                    <tr>
                        <th>Véhicule</th>
                        <th>Type de panne</th>
                        <th>Description</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Clôturée par</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-historique-vehicule').select2({ width: '100%', selectionCssClass: 'select2-sm' });

            function filtresHistorique() {
                return {
                    vehicule_id: $('#filtres-historique-interventions [name=vehicule_id]').val(),
                    type_panne_id: $('#filtres-historique-interventions [name=type_panne_id]').val(),
                    date_debut: $('#filtres-historique-interventions [name=date_debut]').val(),
                    date_fin: $('#filtres-historique-interventions [name=date_fin]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtresHistorique()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-historique-interventions').toggleClass('d-none', !actif);
            }

            function chargerKpis() {
                $.get('{{ route('flotte.interventions.kpis') }}', filtresHistorique(), function (kpis) {
                    $('#kpi-en-cours').text(kpis.en_cours);
                    $('#kpi-ce-mois').text(kpis.ce_mois);
                    $('#kpi-periode').text(kpis.periode);
                });
            }

            actualiserBoutonReset();
            chargerKpis();

            const table = $('#table-historique-interventions').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('flotte.interventions.historique.data') }}',
                    data: (d) => Object.assign(d, filtresHistorique()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'vehicule_code', name: 'vehicule_code' },
                    { data: 'type_panne_libelle', name: 'type_panne_libelle', orderable: false },
                    { data: 'description', name: 'description', orderable: false },
                    { data: 'date_debut', name: 'date_debut' },
                    { data: 'date_fin', name: 'date_fin' },
                    { data: 'cloturee_par', name: 'clotureePar.name', orderable: false },
                ],
                order: [[4, 'desc']],
            });

            $('#filtres-historique-interventions').on('change', function () {
                actualiserBoutonReset();
                table.ajax.reload();
                chargerKpis();
            });

            $('#btn-reset-historique-interventions').on('click', function () {
                $('#filtres-historique-interventions')[0].reset();
                $('.select2-filtre-historique-vehicule').val('').trigger('change');
                actualiserBoutonReset();
                table.ajax.reload();
                chargerKpis();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresHistorique());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-historique-interventions').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.interventions.historique.export.excel') }}');
            });

            $('#btn-export-pdf-historique-interventions').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.interventions.historique.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
