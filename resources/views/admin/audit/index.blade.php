<x-app-layout>
    <x-slot name="header">Journal d'audit</x-slot>

    <div class="card shadow-sm border-0">
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
        $(function () {
            $('#table-audit').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.audit.data') }}',
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'causeur', name: 'causer.name', orderable: false },
                    { data: 'description', name: 'description' },
                ],
                order: [[0, 'desc']],
            });
        });
        </script>
    @endpush
</x-app-layout>
