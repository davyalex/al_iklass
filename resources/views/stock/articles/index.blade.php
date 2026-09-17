<x-app-layout>
    <x-slot name="header">Articles</x-slot>

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div class="d-flex flex-wrap gap-2">
            <input type="text" id="recherche-article" class="form-control" style="max-width: 260px;" placeholder="Rechercher un article...">

            <select id="filtre-categorie" class="form-select" style="max-width: 220px;">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected(request('categorie_id') == $categorie->id)>{{ $categorie->libelle }}</option>
                @endforeach
            </select>
        </div>

        @can('create', \App\Models\Article::class)
            <button type="button" class="btn btn-primary" id="btn-nouvel-article">
                <i class="bi bi-plus-lg me-1"></i>Nouvel article
            </button>
        @endcan
    </div>

    <div class="row g-3" id="grille-articles">
        @forelse ($articles as $article)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 carte-article" data-nom="{{ strtolower($article->nom) }}" data-reference="{{ strtolower($article->reference) }}">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ $article->reference }}</span>
                            @if ($article->quantite_stock <= $article->seuil_alerte)
                                <span class="badge bg-danger">En alerte</span>
                            @endif
                        </div>
                        <h2 class="h6 mb-1">{{ $article->nom }}</h2>
                        <p class="small text-muted mb-2">{{ $article->categorie?->libelle ?? 'Sans catégorie' }}</p>
                        <div class="d-flex justify-content-between small mb-3">
                            <span>Stock : <strong>{{ $article->quantite_stock }}</strong>{{ $article->unite ? ' '.$article->unite : '' }}</span>
                            <span>Seuil : {{ $article->seuil_alerte }}</span>
                        </div>
                        @can('update', $article)
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 btn-modifier-article" data-id="{{ $article->id }}">
                                <i class="bi bi-pencil me-1"></i>Modifier
                            </button>
                        @endcan
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
                            <input type="text" name="reference" class="form-control" required>
                            <div class="invalid-feedback">La référence est obligatoire.</div>
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
                                <label class="form-label">Unité</label>
                                <input type="text" name="unite" class="form-control" placeholder="pièce, litre...">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" class="form-control" value="0" min="0" required>
                                <div class="invalid-feedback">Le seuil doit être un nombre positif ou nul.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prix de vente suggéré (FCFA)</label>
                            <input type="number" name="prix_vente" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback">Le prix doit être un nombre positif ou nul.</div>
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

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-categorie').select2({ dropdownParent: $('#modal-article'), width: '100%' });

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
                $('.select2-categorie').val('').trigger('change');
                modal.show();
            });

            $('.btn-modifier-article').on('click', function () {
                const id = $(this).data('id');
                $.get(`/stock/articles/${id}`, function (article) {
                    resetValidation();
                    $('#article-id').val(article.id);
                    $('#form-article [name=reference]').val(article.reference);
                    $('#form-article [name=nom]').val(article.nom);
                    $('#form-article [name=unite]').val(article.unite);
                    $('#form-article [name=seuil_alerte]').val(article.seuil_alerte);
                    $('#form-article [name=prix_vente]').val(article.prix_vente);
                    $('#form-article [name=description]').val(article.description);
                    $('#article-actif').prop('checked', !!article.actif);
                    $('.select2-categorie').val(article.categorie_id).trigger('change');
                    $('#modal-article-titre').text('Modifier l\'article');
                    modal.show();
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
        });
        </script>
    @endpush
</x-app-layout>
