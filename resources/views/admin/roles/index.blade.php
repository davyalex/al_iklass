@php
    $libellesGroupes = [
        'stock' => 'Stock',
        'users' => 'Utilisateurs',
        'roles' => 'Rôles & permissions',
        'audit' => "Journal d'audit",
    ];
@endphp

<x-app-layout>
    <x-slot name="header">Rôles & permissions</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \Spatie\Permission\Models\Role::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-role">
                <i class="bi bi-plus-lg me-1"></i>Nouveau rôle
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Rôle</th>
                        <th>Type</th>
                        <th>Permissions</th>
                        <th>Utilisateurs</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td class="fw-semibold">{{ $role->name }}</td>
                            <td>
                                @if (in_array($role->name, $rolesProtegees, true))
                                    <span class="badge bg-secondary">Rôle par défaut</span>
                                @else
                                    <span class="badge bg-light text-dark border">Personnalisé</span>
                                @endif
                            </td>
                            <td>
                                @if ($role->name === 'superadmin')
                                    <span class="text-muted small">Accès total (toutes permissions)</span>
                                @else
                                    <span class="small">{{ $role->permissions->count() }} permission(s)</span>
                                @endif
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td class="text-end">
                                @can('update', $role)
                                    @if ($role->name !== 'superadmin')
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-gerer-permissions" data-id="{{ $role->id }}" data-nom="{{ $role->name }}">
                                            <i class="bi bi-shield-check me-1"></i>Permissions
                                        </button>
                                    @endif
                                @endcan
                                @can('delete', $role)
                                    @if (! in_array($role->name, $rolesProtegees, true))
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-role" data-id="{{ $role->id }}" data-nom="{{ $role->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modale nouveau rôle --}}
    <div class="modal fade" id="modal-role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-role">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau rôle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du rôle</label>
                            <input type="text" name="name" class="form-control" placeholder="ex: comptable" required>
                            <div class="form-text">Lettres, chiffres, tirets et underscores uniquement.</div>
                        </div>
                        <label class="form-label">Permissions</label>
                        @foreach ($permissions as $groupe => $items)
                            <div class="mb-2">
                                <div class="fw-semibold small text-muted mb-1">{{ $libellesGroupes[$groupe] ?? ucfirst($groupe) }}</div>
                                <div class="row row-cols-1 row-cols-sm-2">
                                    @foreach ($items as $permission)
                                        <div class="col">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->name }}" id="perm-new-{{ $permission->id }}">
                                                <label class="form-check-label small" for="perm-new-{{ $permission->id }}">{{ $permission->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer le rôle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modale gestion des permissions d'un rôle existant --}}
    <div class="modal fade" id="modal-permissions" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-permissions">
                    <input type="hidden" id="permissions-role-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-permissions-titre">Permissions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="permissions-corps">
                        @foreach ($permissions as $groupe => $items)
                            <div class="mb-2">
                                <div class="fw-semibold small text-muted mb-1">{{ $libellesGroupes[$groupe] ?? ucfirst($groupe) }}</div>
                                <div class="row row-cols-1 row-cols-sm-2">
                                    @foreach ($items as $permission)
                                        <div class="col">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input case-permission" name="permissions[]" value="{{ $permission->name }}" id="perm-edit-{{ $permission->id }}">
                                                <label class="form-check-label small" for="perm-edit-{{ $permission->id }}">{{ $permission->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalRole = new bootstrap.Modal('#modal-role');
            const modalPermissions = new bootstrap.Modal('#modal-permissions');

            $('#btn-nouveau-role').on('click', function () {
                $('#form-role')[0].reset();
                modalRole.show();
            });

            $('#form-role').on('submit', function (e) {
                e.preventDefault();

                $.post('/admin/roles', $(this).serialize())
                    .done(function (res) {
                        modalRole.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            $('.btn-gerer-permissions').on('click', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                $('#permissions-role-id').val(id);
                $('#modal-permissions-titre').text('Permissions — ' + nom);
                $('.case-permission').prop('checked', false);

                $.get(`/admin/roles/${id}`, function (role) {
                    const noms = role.permissions.map(p => p.name);
                    $('.case-permission').each(function () {
                        $(this).prop('checked', noms.includes($(this).val()));
                    });
                    modalPermissions.show();
                });
            });

            $('#form-permissions').on('submit', function (e) {
                e.preventDefault();

                const id = $('#permissions-role-id').val();

                $.post(`/admin/roles/${id}/permissions`, $(this).serialize() + '&_method=PUT')
                    .done(function (res) {
                        modalPermissions.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                    });
            });

            $('.btn-supprimer-role').on('click', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'warning',
                    title: `Supprimer le rôle « ${nom} » ?`,
                    showCancelButton: true,
                    confirmButtonText: 'Supprimer',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/admin/roles/${id}`, { _method: 'DELETE' })
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });
        });
        </script>
    @endpush
</x-app-layout>
