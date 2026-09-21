@php
    $libellesGroupes = [
        'stock' => 'Stock',
        'utilisateurs' => 'Utilisateurs',
        'roles' => 'Rôles & permissions',
        'audit' => "Journal d'audit",
        'unites' => 'Unités',
        'flotte' => 'Flotte',
        'parametres' => 'Paramètres',
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

    <div class="row g-3">
        @forelse ($roles as $role)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm border-0 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ $role->name }}</span>
                            @if (in_array($role->name, $rolesProtegees, true))
                                <span class="badge bg-secondary">Rôle par défaut</span>
                            @else
                                <span class="badge bg-primary">Personnalisé</span>
                            @endif
                        </div>
                        <p class="small text-muted mb-1">
                            @if ($role->name === 'superadmin')
                                Accès total (toutes permissions)
                            @else
                                {{ $role->permissions->count() }} permission(s)
                            @endif
                        </p>
                        <p class="small text-muted mb-3">
                            <i class="bi bi-people me-1"></i>{{ $role->users_count }} utilisateur(s)
                        </p>

                        <div class="d-flex flex-column gap-2">
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
                                        <i class="bi bi-trash me-1"></i>Supprimer
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">Aucun rôle pour le moment.</p>
            </div>
        @endforelse
    </div>

    {{-- Modale nouveau rôle --}}
    <div class="modal fade" id="modal-role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
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
                        <x-permissions-groupees :permissions="$permissions" :libelles="$libellesGroupes" id-prefix="new" />
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
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="form-permissions">
                    <input type="hidden" id="permissions-role-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-permissions-titre">Permissions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="permissions-corps">
                        <x-permissions-groupees :permissions="$permissions" :libelles="$libellesGroupes" id-prefix="edit" checkbox-class="case-permission" />
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
