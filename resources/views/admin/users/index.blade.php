<x-app-layout>
    <x-slot name="header">Utilisateurs</x-slot>

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div class="d-flex flex-wrap gap-2">
            <input type="text" id="recherche-utilisateur" class="form-control" style="max-width: 260px;" placeholder="Rechercher un utilisateur...">

            <select id="filtre-role" class="form-select" style="max-width: 220px;">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                @endforeach
            </select>

            <select id="filtre-statut" class="form-select" style="max-width: 180px;">
                <option value="">Tous les statuts</option>
                <option value="actif" @selected(request('statut') === 'actif')>Actif</option>
                <option value="inactif" @selected(request('statut') === 'inactif')>Inactif</option>
            </select>
        </div>

        @can('create', \App\Models\User::class)
            <button type="button" class="btn btn-primary" id="btn-nouvel-utilisateur">
                <i class="bi bi-plus-lg me-1"></i>Nouvel utilisateur
            </button>
        @endcan
    </div>

    <div class="row g-3" id="grille-utilisateurs">
        @forelse ($users as $utilisateur)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 carte-utilisateur" data-nom="{{ strtolower($utilisateur->name) }}" data-username="{{ strtolower($utilisateur->username) }}">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ '@'.$utilisateur->username }}</span>
                            @if ($utilisateur->isLocked())
                                <span class="badge bg-danger">Verrouillé</span>
                            @elseif ($utilisateur->is_active)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </div>
                        <h2 class="h6 mb-1">{{ $utilisateur->name }}</h2>
                        <p class="small text-muted mb-1">{{ $utilisateur->roles->pluck('name')->join(', ') ?: 'Aucun rôle' }}</p>
                        <p class="small text-muted mb-3">
                            <i class="bi bi-telephone me-1"></i>{{ $utilisateur->telephone }}
                            @if ($utilisateur->email)
                                <br><i class="bi bi-envelope me-1"></i>{{ $utilisateur->email }}
                            @endif
                        </p>

                        <div class="d-flex flex-column gap-2">
                            @can('update', $utilisateur)
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-utilisateur" data-id="{{ $utilisateur->id }}">
                                    <i class="bi bi-pencil me-1"></i>Modifier
                                </button>
                            @endcan
                            @can('resetPassword', $utilisateur)
                                <button type="button" class="btn btn-sm btn-outline-primary btn-reinitialiser-mdp" data-id="{{ $utilisateur->id }}" data-nom="{{ $utilisateur->name }}">
                                    <i class="bi bi-key me-1"></i>Réinitialiser le mot de passe
                                </button>
                            @endcan
                            @can('toggleActive', $utilisateur)
                                @if ($utilisateur->is_active)
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-desactiver-utilisateur" data-id="{{ $utilisateur->id }}" data-nom="{{ $utilisateur->name }}">
                                        <i class="bi bi-slash-circle me-1"></i>Désactiver
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-success btn-activer-utilisateur" data-id="{{ $utilisateur->id }}" data-nom="{{ $utilisateur->name }}">
                                        <i class="bi bi-check-circle me-1"></i>Activer
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">Aucun utilisateur pour le moment.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- Modale création / édition --}}
    <div class="modal fade" id="modal-utilisateur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-utilisateur">
                    <input type="hidden" name="id" id="utilisateur-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-utilisateur-titre">Nouvel utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom et prénom</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email (facultatif)</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select select2-role" required>
                                <option value=""></option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-info small mb-0" id="utilisateur-info-mdp">
                            Un mot de passe à 5 chiffres sera généré automatiquement à la création.
                        </div>
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
            $('.select2-role').select2({ dropdownParent: $('#modal-utilisateur'), width: '100%' });

            $('#recherche-utilisateur').on('input', function () {
                const q = $(this).val().toLowerCase();
                $('.carte-utilisateur').each(function () {
                    const match = $(this).data('nom').toString().includes(q) || $(this).data('username').toString().includes(q);
                    $(this).toggle(match);
                });
            });

            $('#filtre-role, #filtre-statut').on('change', function () {
                const url = new URL(window.location.href);
                const role = $('#filtre-role').val();
                const statut = $('#filtre-statut').val();
                role ? url.searchParams.set('role', role) : url.searchParams.delete('role');
                statut ? url.searchParams.set('statut', statut) : url.searchParams.delete('statut');
                window.location.href = url.toString();
            });

            const modal = new bootstrap.Modal('#modal-utilisateur');

            $('#btn-nouvel-utilisateur').on('click', function () {
                $('#form-utilisateur')[0].reset();
                $('#utilisateur-id').val('');
                $('#modal-utilisateur-titre').text('Nouvel utilisateur');
                $('#utilisateur-info-mdp').show();
                $('.select2-role').val('').trigger('change');
                modal.show();
            });

            $('.btn-modifier-utilisateur').on('click', function () {
                const id = $(this).data('id');
                $.get(`/admin/users/${id}`, function (utilisateur) {
                    $('#utilisateur-id').val(utilisateur.id);
                    $('#form-utilisateur [name=name]').val(utilisateur.name);
                    $('#form-utilisateur [name=username]').val(utilisateur.username);
                    $('#form-utilisateur [name=telephone]').val(utilisateur.telephone);
                    $('#form-utilisateur [name=email]').val(utilisateur.email);
                    $('.select2-role').val(utilisateur.roles[0]?.name ?? '').trigger('change');
                    $('#modal-utilisateur-titre').text('Modifier l\'utilisateur');
                    $('#utilisateur-info-mdp').hide();
                    modal.show();
                });
            });

            $('#form-utilisateur').on('submit', function (e) {
                e.preventDefault();

                const id = $('#utilisateur-id').val();
                const url = id ? `/admin/users/${id}` : '/admin/users';
                const data = $(this).serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        modal.hide();
                        if (res.password) {
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                html: `Mot de passe généré : <span class="fs-4 fw-bold font-monospace">${res.password}</span><br><small class="text-muted">Communiquez-le à l'utilisateur, il ne sera plus affiché.</small>`,
                                confirmButtonText: 'J\'ai noté le mot de passe',
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        }
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            $('.btn-reinitialiser-mdp').on('click', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'question',
                    title: 'Réinitialiser le mot de passe ?',
                    text: `Un nouveau mot de passe sera généré pour ${nom}.`,
                    showCancelButton: true,
                    confirmButtonText: 'Réinitialiser',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/admin/users/${id}/reset-password`)
                        .done(function (res) {
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                html: `Nouveau mot de passe : <span class="fs-4 fw-bold font-monospace">${res.password}</span><br><small class="text-muted">Communiquez-le à l'utilisateur, il ne sera plus affiché.</small>`,
                                confirmButtonText: 'J\'ai noté le mot de passe',
                            }).then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $('.btn-activer-utilisateur, .btn-desactiver-utilisateur').on('click', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');
                const activer = $(this).hasClass('btn-activer-utilisateur');

                Swal.fire({
                    icon: 'warning',
                    title: activer ? 'Activer ce compte ?' : 'Désactiver ce compte ?',
                    text: nom,
                    showCancelButton: true,
                    confirmButtonText: activer ? 'Activer' : 'Désactiver',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/admin/users/${id}/${activer ? 'activate' : 'deactivate'}`, { _method: 'PATCH' })
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
