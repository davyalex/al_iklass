<x-app-layout>
    <x-slot name="header">Mouvements de stock</x-slot>

    <div class="row g-3 mb-3 row-cols-2 row-cols-lg-4">
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-in-down text-success me-1"></i>Entrées du jour</span>
                    <span class="badge rounded-pill bg-success">{{ $kpis['entrees_jour'] }}</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-up text-danger me-1"></i>Sorties du jour</span>
                    <span class="badge rounded-pill bg-danger">{{ $kpis['sorties_jour'] }}</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-in-down text-success me-1"></i>Entrées du mois</span>
                    <span class="badge rounded-pill bg-success">{{ $kpis['entrees_mois'] }}</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-up text-danger me-1"></i>Sorties du mois</span>
                    <span class="badge rounded-pill bg-danger">{{ $kpis['sorties_mois'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-3 d-none" id="banniere-article-filtre">
        <span><i class="bi bi-funnel me-2"></i>Historique filtré sur : <strong id="banniere-article-texte"></strong></span>
    </div>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-mouvements" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ request('date_debut') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ request('date_fin') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Article</label>
                    <select name="article_id" class="form-select form-select-sm select2-filtre-article">
                        <option value="">Tous</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}" @selected($articlePreselectionne?->id === $article->id)>{{ $article->reference }} — {{ $article->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="entree">Entrée</option>
                        <option value="sortie">Sortie</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 al-filtres-actions justify-content-md-end">
                    <x-filtre-reset id="btn-reset-mouvements" />
                    <x-export-dropdown id-suffix="mouvements" />
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-mouvements">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Article</th>
                        <th>Type</th>
                        <th class="text-end">Quantité</th>
                        <th class="text-end">Prix unitaire</th>
                        <th>Origine</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-article').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            function filtresMouvements() {
                return {
                    date_debut: $('#filtres-mouvements [name=date_debut]').val(),
                    date_fin: $('#filtres-mouvements [name=date_fin]').val(),
                    article_id: $('#filtres-mouvements [name=article_id]').val(),
                    type: $('#filtres-mouvements [name=type]').val(),
                };
            }

            function actualiserBoutonResetMouvements() {
                const actif = Object.values(filtresMouvements()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-mouvements').toggleClass('d-none', !actif);
            }

            // La bannière reflète toujours l'article réellement sélectionné dans le
            // filtre (et disparaît si on le change ou le vide), plutôt que de rester
            // figée sur l'article d'arrivée depuis Suivi de stock.
            function actualiserBanniereArticle() {
                const id = $('#filtres-mouvements [name=article_id]').val();
                if (id) {
                    $('#banniere-article-texte').text($('.select2-filtre-article option:selected').text());
                    $('#banniere-article-filtre').removeClass('d-none');
                } else {
                    $('#banniere-article-filtre').addClass('d-none');
                }
            }

            $('#filtres-mouvements').on('change input', actualiserBoutonResetMouvements);
            $('#filtres-mouvements').on('change', actualiserBanniereArticle);
            actualiserBoutonResetMouvements();
            actualiserBanniereArticle();

            const tableMouvements = $('#table-mouvements').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.mouvements.data') }}',
                    data: (d) => Object.assign(d, filtresMouvements()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_mouvement', name: 'date_mouvement' },
                    { data: 'article_libelle', name: 'article_nom', orderable: false },
                    { data: 'type_badge', name: 'type', orderable: false },
                    { data: 'quantite', name: 'quantite', className: 'text-end' },
                    { data: 'prix_unitaire', name: 'prix_unitaire', className: 'text-end' },
                    { data: 'origine', name: 'origine', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            // Filtrage automatique : chaque changement (date, article, type)
            // relance immédiatement le tableau, sans bouton "Filtrer" à cliquer.
            $('#filtres-mouvements').on('change', () => tableMouvements.ajax.reload());

            $('#btn-reset-mouvements').on('click', function () {
                $('#filtres-mouvements')[0].reset();
                $('.select2-filtre-article').val('').trigger('change');
                tableMouvements.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresMouvements());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-mouvements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.mouvements.export.excel') }}');
            });

            $('#btn-export-pdf-mouvements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.mouvements.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
