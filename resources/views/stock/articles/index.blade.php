<x-app-layout>
    <x-slot name="header">Articles</x-slot>

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end mb-3">
        @can('create', \App\Models\Article::class)
            <button type="button" class="btn btn-outline-secondary" id="btn-gerer-categories">
                <i class="bi bi-tags me-1"></i>Catégories
            </button>
            <button type="button" class="btn btn-primary" id="btn-nouvel-article">
                <i class="bi bi-plus-lg me-1"></i>Nouvel article
            </button>
        @endcan
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="d-flex flex-nowrap gap-2">
            <input type="text" id="recherche-article" class="form-control flex-grow-1" style="min-width: 0; max-width: 300px;" placeholder="Rechercher un article...">

            <select id="filtre-categorie" class="form-select" style="max-width: 200px; flex-shrink: 0;">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected(request('categorie_id') == $categorie->id)>{{ $categorie->libelle }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-check form-switch mb-0 text-nowrap">
            <input class="form-check-input" type="checkbox" role="switch" id="filtre-en-alerte" @checked(request()->boolean('en_alerte'))>
            <label class="form-check-label" for="filtre-en-alerte">En alerte</label>
        </div>

        <div class="ms-auto">
            <x-export-dropdown id-suffix="articles" />
        </div>
    </div>

    <div class="row g-3" id="grille-articles">
        @forelse ($articles as $article)
            @php $enAlerte = $article->quantite_stock <= $article->seuil_alerte; @endphp
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 carte-article" data-nom="{{ strtolower($article->nom) }}" data-reference="{{ strtolower($article->reference) }}">
                <div class="card h-100 shadow-sm {{ $enAlerte ? 'border-danger border-2' : 'border-0' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ $article->reference }}</span>
                            @if ($enAlerte)
                                <span class="badge bg-danger">En alerte</span>
                            @endif
                        </div>
                        <h2 class="h6 mb-1">{{ $article->nom }}</h2>
                        <p class="small text-muted mb-2">{{ $article->categorie?->libelle ?? 'Sans catégorie' }}</p>
                        <div class="d-flex justify-content-between small mb-3">
                            <span>Stock : <strong>{{ $article->quantite_stock }}</strong>{{ $article->unite ? ' '.$article->unite->libelle : '' }}</span>
                            <span>Seuil : {{ $article->seuil_alerte }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            @can('update', $article)
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill btn-modifier-article" data-id="{{ $article->id }}">
                                    <i class="bi bi-pencil me-1"></i>Modifier
                                </button>
                            @endcan
                            @can('delete', $article)
                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-article" data-id="{{ $article->id }}" data-nom="{{ $article->nom }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">Aucun article pour le moment.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $articles->links() }}
    </div>

    {{-- Modale création / édition --}}
    <div class="modal fade" id="modal-article" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-article" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="article-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-article-titre">Nouvel article</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Référence</label>
                            <input type="text" name="reference" class="form-control">
                            <div class="form-text">Laisser vide pour générer une référence automatiquement.</div>
                            <div class="invalid-feedback">Cette référence est déjà utilisée.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" required>
                            <div class="invalid-feedback">Le nom est obligatoire.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie_id" class="form-select select2-categorie">
                                <option value="">Sans catégorie</option>
                                @foreach ($categories as $categorie)
                                    <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0">Unité</label>
                                    @can('unites.gerer')
                                        <a href="{{ route('admin.unites.index') }}" target="_blank" class="small">Gérer les unités</a>
                                    @endcan
                                </div>
                                <select name="unite_id" class="form-select select2-unite">
                                    <option value="">Sans unité</option>
                                    @foreach ($unites as $unite)
                                        <option value="{{ $unite->id }}">{{ $unite->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" class="form-control" value="0" min="0" required>
                                <div class="invalid-feedback">Le seuil doit être un nombre positif ou nul.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="actif" id="article-actif" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="article-actif">Actif</label>
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

    {{-- Modale gestion des catégories --}}
    <div class="modal fade" id="modal-categories" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Catégories d'articles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-categorie" class="needs-validation row g-2 align-items-end mb-3" novalidate>
                        <input type="hidden" name="id" id="categorie-id">
                        <div class="col-8">
                            <label class="form-label">Libellé</label>
                            <input type="text" name="libelle" class="form-control" required>
                            <div class="invalid-feedback">Le libellé est obligatoire.</div>
                        </div>
                        <div class="col-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill" id="btn-categorie-submit">Ajouter</button>
                        </div>
                        <div class="col-12 form-check">
                            <input type="checkbox" name="actif" id="categorie-actif" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="categorie-actif">Active</label>
                        </div>
                        <div class="col-12" id="categorie-annuler-wrapper" style="display: none;">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-categorie-annuler">Annuler la modification</button>
                        </div>
                    </form>

                    <hr>

                    <ul class="list-group" id="liste-categories">
                        @foreach ($categoriesToutes as $categorie)
                            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $categorie->id }}" data-code="{{ $categorie->code }}" data-libelle="{{ $categorie->libelle }}" data-actif="{{ $categorie->actif ? 1 : 0 }}">
                                <span>
                                    <span class="badge bg-light text-dark border me-1">{{ $categorie->code }}</span>
                                    {{ $categorie->libelle }}
                                    @unless ($categorie->actif)
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endunless
                                </span>
                                <span class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-categorie">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-categorie">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-categorie, .select2-unite').select2({ dropdownParent: $('#modal-article'), width: '100%' });

            $('#recherche-article').on('input', function () {
                const q = $(this).val().toLowerCase();
                $('.carte-article').each(function () {
                    const match = $(this).data('nom').includes(q) || $(this).data('reference').includes(q);
                    $(this).toggle(match);
                });
            });

            $('#filtre-categorie').on('change', function () {
                const url = new URL(window.location.href);
                if ($(this).val()) {
                    url.searchParams.set('categorie_id', $(this).val());
                } else {
                    url.searchParams.delete('categorie_id');
                }
                window.location.href = url.toString();
            });

            $('#filtre-en-alerte').on('change', function () {
                const url = new URL(window.location.href);
                if ($(this).is(':checked')) {
                    url.searchParams.set('en_alerte', '1');
                } else {
                    url.searchParams.delete('en_alerte');
                }
                window.location.href = url.toString();
            });

            function urlAvecFiltresArticles(base) {
                const params = new URLSearchParams(window.location.search);
                return base + (params.toString() ? '?' + params.toString() : '');
            }

            $('#btn-export-excel-articles').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltresArticles('{{ route('stock.articles.export.excel') }}');
            });

            $('#btn-export-pdf-articles').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltresArticles('{{ route('stock.articles.export.pdf') }}');
            });

            const modal = new bootstrap.Modal('#modal-article');
            const $form = $('#form-article');

            function resetValidation() {
                $form.removeClass('was-validated');
                $form.find('.is-invalid').removeClass('is-invalid');
            }

            $('#btn-nouvel-article').on('click', function () {
                $form[0].reset();
                resetValidation();
                $('#article-id').val('');
                $('#modal-article-titre').text('Nouvel article');
                $('.select2-categorie, .select2-unite').val('').trigger('change');
                modal.show();
            });

            $('.btn-modifier-article').on('click', function () {
                const id = $(this).data('id');
                $.get(`/stock/articles/${id}`, function (article) {
                    resetValidation();
                    $('#article-id').val(article.id);
                    $('#form-article [name=reference]').val(article.reference);
                    $('#form-article [name=nom]').val(article.nom);
                    $('#form-article [name=seuil_alerte]').val(article.seuil_alerte);
                    $('#form-article [name=description]').val(article.description);
                    $('#article-actif').prop('checked', !!article.actif);
                    $('.select2-categorie').val(article.categorie_id).trigger('change');
                    $('.select2-unite').val(article.unite_id).trigger('change');
                    $('#modal-article-titre').text('Modifier l\'article');
                    modal.show();
                });
            });

            $('.btn-supprimer-article').on('click', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'warning',
                    text: `Archiver l'article « ${nom} » ?`,
                    showCancelButton: true,
                    confirmButtonText: 'Archiver',
                    cancelButtonText: 'Annuler',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({ url: `/stock/articles/${id}`, method: 'DELETE' })
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $form.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const formEl = $form[0];
                $form.find('.is-invalid').removeClass('is-invalid');

                if (!formEl.checkValidity()) {
                    $form.addClass('was-validated');
                    return;
                }

                const id = $('#article-id').val();
                const url = id ? `/stock/articles/${id}` : '/stock/articles';
                const data = $form.serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }
                if (!$('#article-actif').is(':checked')) {
                    data.push({ name: 'actif', value: '0' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        resetValidation();
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                const $input = $form.find(`[name="${field}"]`);
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        }
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            // --- Gestion des catégories ---
            const modalCategories = new bootstrap.Modal('#modal-categories');
            const $formCategorie = $('#form-categorie');

            function rafraichirSelectCategorie(categoriesActives) {
                const $select = $('.select2-categorie');
                const valeurActuelle = $select.val();
                $select.empty().append('<option value="">Sans catégorie</option>');
                categoriesActives.forEach(function (c) {
                    $select.append(`<option value="${c.id}">${$('<div>').text(c.libelle).html()}</option>`);
                });
                $select.val(valeurActuelle).trigger('change');
            }

            function rafraichirListeCategories(categoriesToutes) {
                const $liste = $('#liste-categories');
                $liste.empty();
                categoriesToutes.forEach(function (c) {
                    const badgeInactive = c.actif ? '' : '<span class="badge bg-secondary">Inactive</span>';
                    const codeHtml = $('<div>').text(c.code).html();
                    const libelleHtml = $('<div>').text(c.libelle).html();
                    $liste.append(`
                        <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${c.id}" data-code="${codeHtml}" data-libelle="${libelleHtml}" data-actif="${c.actif ? 1 : 0}">
                            <span>
                                <span class="badge bg-light text-dark border me-1">${codeHtml}</span>
                                ${libelleHtml}
                                ${badgeInactive}
                            </span>
                            <span class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-categorie"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-categorie"><i class="bi bi-trash"></i></button>
                            </span>
                        </li>
                    `);
                });
            }

            function resetFormCategorie() {
                $formCategorie[0].reset();
                $formCategorie.removeClass('was-validated');
                $formCategorie.find('.is-invalid').removeClass('is-invalid');
                $('#categorie-id').val('');
                $('#btn-categorie-submit').text('Ajouter');
                $('#categorie-annuler-wrapper').hide();
            }

            $('#btn-gerer-categories').on('click', function () {
                resetFormCategorie();
                modalCategories.show();
            });

            $('#btn-categorie-annuler').on('click', resetFormCategorie);

            $('#liste-categories').on('click', '.btn-modifier-categorie', function () {
                const $li = $(this).closest('li');
                $('#categorie-id').val($li.data('id'));
                $formCategorie.find('[name=libelle]').val($li.data('libelle'));
                $('#categorie-actif').prop('checked', $li.data('actif') == 1);
                $('#btn-categorie-submit').text('Mettre à jour');
                $('#categorie-annuler-wrapper').show();
                $formCategorie.removeClass('was-validated');
                $formCategorie.find('.is-invalid').removeClass('is-invalid');
            });

            $('#liste-categories').on('click', '.btn-supprimer-categorie', function () {
                const $li = $(this).closest('li');
                const id = $li.data('id');

                Swal.fire({
                    icon: 'warning',
                    text: `Archiver la catégorie « ${$li.data('libelle')} » ?`,
                    showCancelButton: true,
                    confirmButtonText: 'Archiver',
                    cancelButtonText: 'Annuler',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({ url: `/stock/categories-article/${id}`, method: 'DELETE' })
                        .done(function (res) {
                            rafraichirListeCategories(res.categoriesToutes);
                            rafraichirSelectCategorie(res.categoriesActives);
                            if ($('#categorie-id').val() == id) {
                                resetFormCategorie();
                            }
                            Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $formCategorie.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                $formCategorie.find('.is-invalid').removeClass('is-invalid');

                if (!$formCategorie[0].checkValidity()) {
                    $formCategorie.addClass('was-validated');
                    return;
                }

                const id = $('#categorie-id').val();
                const url = id ? `/stock/categories-article/${id}` : '/stock/categories-article';
                const data = $formCategorie.serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }
                if (!$('#categorie-actif').is(':checked')) {
                    data.push({ name: 'actif', value: '0' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        rafraichirListeCategories(res.categoriesToutes);
                        rafraichirSelectCategorie(res.categoriesActives);
                        resetFormCategorie();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                    })
                    .fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $formCategorie.addClass('was-validated');
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                const $input = $formCategorie.find(`[name="${field}"]`);
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        }
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });
        });
        </script>
    @endpush
</x-app-layout>
