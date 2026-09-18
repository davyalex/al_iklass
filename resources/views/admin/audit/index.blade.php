<x-app-layout>
    <x-slot name="header">Journal d'audit</x-slot>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-audit" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Utilisateur</label>
                    <select name="causer_id" class="form-select form-select-sm select2-filtre-utilisateur">
                        <option value="">Tous</option>
                        @foreach ($utilisateurs as $utilisateur)
                            <option value="{{ $utilisateur->id }}">{{ $utilisateur->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-audit">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-audit">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="audit" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-audit">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-utilisateur').select2({ width: '100%', placeholder: 'Tous' });

            function filtresAudit() {
                return {
                    date_debut: $('#filtres-audit [name=date_debut]').val(),
                    date_fin: $('#filtres-audit [name=date_fin]').val(),
                    causer_id: $('#filtres-audit [name=causer_id]').val(),
                };
            }

            const tableAudit = $('#table-audit').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.audit.data') }}',
                    data: (d) => Object.assign(d, filtresAudit()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'causeur', name: 'causer.name', orderable: false },
                    { data: 'description', name: 'description' },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-audit').on('click', () => tableAudit.ajax.reload());

            $('#btn-reset-audit').on('click', function () {
                $('#filtres-audit')[0].reset();
                $('.select2-filtre-utilisateur').val('').trigger('change');
                tableAudit.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresAudit());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-audit').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('admin.audit.export.excel') }}');
            });

            $('#btn-export-pdf-audit').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('admin.audit.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
