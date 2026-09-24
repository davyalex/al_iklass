<x-app-layout>
    <x-slot name="header">Prêteurs</x-slot>

    <div class="al-page-actions">
        @can('financements.type.gerer')
            <button type="button" class="btn btn-outline-secondary" id="btn-types-preteur">
                <i class="bi bi-tags me-1"></i>Types de prêteur
            </button>
        @endcan
        @can('create', \App\Models\Preteur::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-preteur">
                <i class="bi bi-plus-lg me-1"></i>Nouveau prêteur
            </button>
        @endcan
    </div>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('financements.preteurs.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Nom</label>
                    <input type="text" name="nom" class="form-control form-control-sm" placeholder="Rechercher un prêteur..." value="{{ request('nom') }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type_preteur_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($typesPreteur as $type)
                            <option value="{{ $type->id }}" @selected(request('type_preteur_id') == $type->id)>{{ $type->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="actif" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="1" @selected(request('actif') === '1')>Actif</option>
                        <option value="0" @selected(request('actif') === '0')>Inactif</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 al-filtres-actions">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    @if (request()->anyFilled(['nom', 'type_preteur_id', 'actif']))
                        <x-filtre-reset :href="route('financements.preteurs.index')" />
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($preteurs as $preteur)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm border-0 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h6 mb-0">{{ $preteur->nom }}</h2>
                            @if ($preteur->actif)
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </div>
                        <p class="small mb-1">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-{{ $preteur->type_preteur_code === 'banque' ? 'bank' : 'person' }} me-1"></i>{{ $preteur->type_preteur_libelle }}
                            </span>
                        </p>
                        <p class="small text-muted mb-1">{{ $preteur->telephone ?? '—' }}</p>
                        <p class="small text-muted mb-3">{{ $preteur->email ?? '—' }}</p>
                        <div class="d-flex justify-content-between small mb-3">
                            <span>Emprunts : <strong>{{ $preteur->financements_count }}</strong></span>
                            <span>
                                Reste dû :
                                <strong class="{{ (float) $preteur->solde_du > 0 ? 'text-danger' : '' }}">
                                    {{ \App\Support\Money::format($preteur->solde_du ?? 0) }} FCFA
                                </strong>
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('financements.preteurs.compte', $preteur) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-eye me-1"></i>Compte
                            </a>
                            @can('update', $preteur)
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-preteur" data-id="{{ $preteur->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endcan
                            @can('delete', $preteur)
                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-preteur" data-id="{{ $preteur->id }}" data-nom="{{ $preteur->nom }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">
                    @if (request()->anyFilled(['nom', 'type_preteur_id', 'actif']))
                        Aucun prêteur ne correspond à ce filtre.
                    @else
                        Aucun prêteur pour le moment.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $preteurs->links() }}</div>

    <div class="modal fade" id="modal-preteur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-preteur">
                    <input type="hidden" name="id" id="preteur-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-preteur-titre">Nouveau prêteur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex : Banque Atlantique, Jean Kouassi..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type_preteur_id" class="form-select" required>
                                <option value=""></option>
                                @foreach ($typesPreteur as $type)
                                    <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="adresse" class="form-control">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="actif" id="preteur-actif" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="preteur-actif">Actif</label>
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

    @can('financements.type.gerer')
        <div class="modal fade" id="modal-types-preteur" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Types de prêteur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group mb-3" id="liste-types-preteur">
                            @foreach ($typesPreteur as $type)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $type->libelle }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-editer-type-preteur" data-id="{{ $type->id }}" data-libelle="{{ $type->libelle }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <form id="form-type-preteur" class="row g-2 align-items-end">
                            <input type="hidden" name="id" id="type-preteur-id">
                            <div class="col-8">
                                <label class="form-label small mb-1">Libellé</label>
                                <input type="text" name="libelle" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal('#modal-preteur');

            $('#btn-nouveau-preteur').on('click', function () {
                $('#form-preteur')[0].reset();
                $('#preteur-id').val('');
                $('#modal-preteur-titre').text('Nouveau prêteur');
                modal.show();
            });

            $('.btn-supprimer-preteur').on('click', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'warning',
                    text: `Archiver le prêteur « ${nom} » ?`,
                    showCancelButton: true,
                    confirmButtonText: 'Archiver',
                    cancelButtonText: 'Annuler',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({ url: `/financements/preteurs/${id}`, method: 'DELETE' })
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $('.btn-modifier-preteur').on('click', function () {
                const id = $(this).data('id');
                $.get(`/financements/preteurs/${id}`, function (p) {
                    $('#preteur-id').val(p.id);
                    $('#form-preteur [name=nom]').val(p.nom);
                    $('#form-preteur [name=type_preteur_id]').val(p.type_preteur_id);
                    $('#form-preteur [name=telephone]').val(p.telephone);
                    $('#form-preteur [name=email]').val(p.email);
                    $('#form-preteur [name=adresse]').val(p.adresse);
                    $('#preteur-actif').prop('checked', !!p.actif);
                    $('#modal-preteur-titre').text('Modifier le prêteur');
                    modal.show();
                });
            });

            $('#form-preteur').on('submit', function (e) {
                e.preventDefault();

                const id = $('#preteur-id').val();
                const url = id ? `/financements/preteurs/${id}` : '/financements/preteurs';
                const data = $(this).serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }
                if (!$('#preteur-actif').is(':checked')) {
                    data.push({ name: 'actif', value: '0' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            @can('financements.type.gerer')
            const modalTypes = new bootstrap.Modal('#modal-types-preteur');

            $('#btn-types-preteur').on('click', function () {
                $('#form-type-preteur')[0].reset();
                $('#type-preteur-id').val('');
                modalTypes.show();
            });

            $('#liste-types-preteur').on('click', '.btn-editer-type-preteur', function () {
                $('#type-preteur-id').val($(this).data('id'));
                $('#form-type-preteur [name=libelle]').val($(this).data('libelle'));
            });

            $('#form-type-preteur').on('submit', function (e) {
                e.preventDefault();

                const id = $('#type-preteur-id').val();
                const url = id ? `/financements/types/${id}` : '/financements/types';
                const data = $(this).serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });
            @endcan
        });
        </script>
    @endpush
</x-app-layout>
