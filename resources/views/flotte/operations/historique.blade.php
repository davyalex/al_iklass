<x-app-layout>
    <x-slot name="header">Historique des opérations</x-slot>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('flotte.operations.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux opérations
        </a>
    </div>

    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <form id="filtres-historique-operations" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Véhicule</label>
                    <select name="vehicule_id" class="form-select form-select-sm select2-filtre-historique-vehicule">
                        <option value="">Tous les véhicules</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->id }}">{{ $vehicule->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type_operation_id" class="form-select form-select-sm">
                        <option value="">Tous les types</option>
                        @foreach ($typesOperation as $type)
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
                <div class="col-12 col-md d-flex justify-content-md-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-historique-operations" title="Réinitialiser les filtres">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <x-export-dropdown id-suffix="historique-operations" />
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-historique-operations">
                <thead>
                    <tr>
                        <th>Date de réalisation</th>
                        <th>Véhicule</th>
                        <th>Type</th>
                        <th>Échéance prévue</th>
                        <th>Réalisé par</th>
                        <th>Commentaire</th>
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
                    vehicule_id: $('#filtres-historique-operations [name=vehicule_id]').val(),
                    type_operation_id: $('#filtres-historique-operations [name=type_operation_id]').val(),
                    date_debut: $('#filtres-historique-operations [name=date_debut]').val(),
                    date_fin: $('#filtres-historique-operations [name=date_fin]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtresHistorique()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-historique-operations').toggleClass('d-none', !actif);
            }

            const table = $('#table-historique-operations').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('flotte.operations.historique.data') }}',
                    data: (d) => Object.assign(d, filtresHistorique()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_realisation', name: 'date_realisation' },
                    { data: 'vehicule_code', name: 'vehicule_code' },
                    { data: 'type_operation_libelle', name: 'type_operation_libelle' },
                    { data: 'date_echeance', name: 'date_echeance' },
                    { data: 'realise_par', name: 'realisePar.name', orderable: false },
                    { data: 'commentaire', name: 'commentaire', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            $('#filtres-historique-operations').on('change', function () {
                actualiserBoutonReset();
                table.ajax.reload();
            });

            $('#btn-reset-historique-operations').on('click', function () {
                $('#filtres-historique-operations')[0].reset();
                $('.select2-filtre-historique-vehicule').val('').trigger('change');
                actualiserBoutonReset();
                table.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresHistorique());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-historique-operations').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.operations.historique.export.excel') }}');
            });

            $('#btn-export-pdf-historique-operations').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.operations.historique.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
