<x-app-layout>
    <x-slot name="header">Suivi de stock</x-slot>

    <div class="row g-3 mb-3 row-cols-2 row-cols-lg-4">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">État du stock</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['nb_articles'] }} articles</div>
                    <div class="small text-muted">{{ $kpis['total_pieces'] }} pièces au total</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Valeur du stock restant</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($kpis['valeur_stock']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Articles en alerte</div>
                    <div class="h5 mb-0 {{ $kpis['en_alerte'] > 0 ? 'text-danger' : '' }}">{{ $kpis['en_alerte'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Dettes fournisseurs</div>
                    <div class="h5 mb-0 text-danger">{{ \App\Support\Money::format($kpis['dettes_fournisseurs']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Achats (période)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-achats-periode">{{ \App\Support\Money::format($kpis['achats_periode']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Stock utilisé (interne, période)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-stock-utilise-interne">{{ \App\Support\Money::format($kpis['stock_utilise_interne']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Vendu externe (période)</div>
                    <div class="h5 mb-0 text-success" id="kpi-montant-vendu-externe">{{ \App\Support\Money::format($kpis['montant_vendu_externe']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3 row-cols-3">
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-in-down text-success me-1"></i>Entrées (période)</span>
                    <span class="badge rounded-pill bg-success" id="kpi-entrees-count">{{ $kpis['entrees_count'] }}</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-up text-warning me-1"></i>Sorties internes (période)</span>
                    <span class="badge rounded-pill bg-warning text-dark" id="kpi-sorties-interne-count">{{ $kpis['sorties_interne_count'] }}</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 bg-white h-100">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted"><i class="bi bi-box-arrow-up text-danger me-1"></i>Sorties externes (période)</span>
                    <span class="badge rounded-pill bg-danger" id="kpi-sorties-externe-count">{{ $kpis['sorties_externe_count'] }}</span>
                </div>
            </div>
        </div>
    </div>
    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i>« Achats », « Stock utilisé », « Vendu externe » et les compteurs entrées/sorties suivent le filtre de période ci-dessous (mois en cours par défaut). Les autres indicateurs reflètent l'état du stock à l'instant présent.
    </p>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-etat-stock" class="row g-2 align-items-end">
                <div class="col-12 col-md-5 col-lg-4">
                    <label class="form-label small mb-1">Période</label>
                    <div class="d-flex align-items-center gap-1">
                        <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                        <span class="text-muted small">à</span>
                        <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Produit</label>
                    <select name="article_id" class="form-select form-select-sm select2-filtre-article">
                        <option value="">Tous</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}">{{ $article->reference }} — {{ $article->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type_mouvement" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="entree">Entrée</option>
                        <option value="sortie">Sortie</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 col-lg-1 d-flex align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="en_alerte" id="filtre-en-alerte-etat">
                        <label class="form-check-label small" for="filtre-en-alerte-etat">Alerte</label>
                    </div>
                </div>
                <div class="col-12 col-lg-2 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-etat-stock">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="etat-stock" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-etat-stock">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Nom</th>
                        <th class="text-end">Entrées</th>
                        <th class="text-end">Sorties</th>
                        <th class="text-end">Stock disponible</th>
                        <th class="text-end">Prix d'achat</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 60px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale détail article --}}
    <div class="modal fade" id="modal-detail-article" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="detail-article-nom">—</h5>
                        <span id="detail-article-statut"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="small text-muted">Référence</div>
                            <div class="fw-semibold" id="detail-article-reference">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Catégorie</div>
                            <div class="fw-semibold" id="detail-article-categorie">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Unité</div>
                            <div class="fw-semibold" id="detail-article-unite">—</div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3">
                            <div class="small text-muted">Quantité en stock</div>
                            <div class="fw-semibold" id="detail-article-quantite">—</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted">Seuil d'alerte</div>
                            <div class="fw-semibold" id="detail-article-seuil">—</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted">Prix d'achat</div>
                            <div class="fw-semibold" id="detail-article-prix">—</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="small text-muted">Valorisation</div>
                            <div class="fw-semibold" id="detail-article-valorisation">—</div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="small text-uppercase text-muted mb-2">Derniers mouvements</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Quantité</th>
                                </tr>
                            </thead>
                            <tbody id="detail-article-mouvements"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalDetailArticle = new bootstrap.Modal('#modal-detail-article');

            const TYPES_MOUVEMENT = {
                entree: { libelle: 'Entrée', classe: 'bg-success' },
                sortie_interne: { libelle: 'Sortie interne', classe: 'bg-warning text-dark' },
                sortie_externe: { libelle: 'Sortie externe', classe: 'bg-danger' },
            };

            $('.select2-filtre-article').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            // Périodes par défaut (mois en cours), telles que préremplies dans le
            // formulaire au chargement : servent de référence pour savoir si
            // l'utilisateur s'est réellement écarté du filtre par défaut.
            const DATE_DEBUT_DEFAUT = $('#filtres-etat-stock [name=date_debut]').val();
            const DATE_FIN_DEFAUT = $('#filtres-etat-stock [name=date_fin]').val();

            function filtresEtatStock() {
                return {
                    date_debut: $('#filtres-etat-stock [name=date_debut]').val(),
                    date_fin: $('#filtres-etat-stock [name=date_fin]').val(),
                    article_id: $('#filtres-etat-stock [name=article_id]').val(),
                    type_mouvement: $('#filtres-etat-stock [name=type_mouvement]').val(),
                    en_alerte: $('#filtres-etat-stock [name=en_alerte]').is(':checked') ? 1 : '',
                };
            }

            function actualiserBoutonResetEtatStock() {
                const f = filtresEtatStock();
                const actif = Boolean(f.article_id || f.type_mouvement || f.en_alerte)
                    || f.date_debut !== DATE_DEBUT_DEFAUT
                    || f.date_fin !== DATE_FIN_DEFAUT;
                $('#btn-reset-etat-stock').toggleClass('d-none', !actif);
            }

            $('#filtres-etat-stock').on('change input', actualiserBoutonResetEtatStock);
            actualiserBoutonResetEtatStock();

            const tableEtatStock = $('#table-etat-stock').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.etat-stock.data') }}',
                    data: (d) => Object.assign(d, filtresEtatStock()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'reference', name: 'reference' },
                    { data: 'nom', name: 'nom' },
                    { data: 'entrees_count', name: 'entrees_count', className: 'text-end', orderable: false },
                    { data: 'sorties_count', name: 'sorties_count', className: 'text-end', orderable: false },
                    { data: 'quantite_stock', name: 'quantite_stock', className: 'text-end' },
                    { data: 'prix_achat', name: 'prix_achat', className: 'text-end' },
                    { data: 'statut_badge', name: 'actif', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (article) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-article" data-id="${article.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[1, 'asc']],
                createdRow: function (row, data) {
                    // Un article desactive doit sauter aux yeux : la ligne entiere est
                    // grisee, quel que soit son statut d'alerte (moins pertinent pour
                    // un article qu'on ne vend/achete plus).
                    if (!data.actif) {
                        $(row).addClass('text-muted').css('opacity', 0.55);
                    } else if (data.en_alerte) {
                        $(row).addClass('table-danger');
                    }
                },
            });

            function rafraichirKpisEtatStock() {
                $.get('{{ route('stock.etat-stock.kpis') }}', filtresEtatStock(), function (kpis) {
                    $('#kpi-achats-periode').text(formatMontant(kpis.achats_periode) + ' FCFA');
                    $('#kpi-stock-utilise-interne').text(formatMontant(kpis.stock_utilise_interne) + ' FCFA');
                    $('#kpi-montant-vendu-externe').text(formatMontant(kpis.montant_vendu_externe) + ' FCFA');
                    $('#kpi-entrees-count').text(kpis.entrees_count);
                    $('#kpi-sorties-interne-count').text(kpis.sorties_interne_count);
                    $('#kpi-sorties-externe-count').text(kpis.sorties_externe_count);
                });
            }

            // Filtrage automatique : chaque changement (date, produit, type,
            // alerte) relance immédiatement le tableau et les KPI, sans bouton
            // "Filtrer" à cliquer.
            $('#filtres-etat-stock').on('change', function () {
                tableEtatStock.ajax.reload();
                rafraichirKpisEtatStock();
            });

            $('#btn-reset-etat-stock').on('click', function () {
                $('#filtres-etat-stock')[0].reset();
                $('.select2-filtre-article').val('').trigger('change');
                tableEtatStock.ajax.reload();
                rafraichirKpisEtatStock();
            });

            $('#table-etat-stock').on('click', '.btn-detail-article', function () {
                const id = $(this).data('id');

                $.get(`/stock/etat-stock/${id}`, function (reponse) {
                    const article = reponse.article;

                    $('#detail-article-nom').text(article.nom);
                    $('#detail-article-statut').html(article.actif
                        ? '<span class="badge bg-light text-dark border">Actif</span>'
                        : '<span class="badge bg-secondary">Inactif</span>');
                    $('#detail-article-reference').text(article.reference);
                    $('#detail-article-categorie').text(article.categorie?.libelle ?? 'Sans catégorie');
                    $('#detail-article-unite').text(article.unite?.libelle ?? '—');
                    $('#detail-article-quantite').text(article.quantite_stock);
                    $('#detail-article-seuil').text(article.seuil_alerte);
                    $('#detail-article-prix').text(formatMontant(article.prix_achat) + ' FCFA');
                    $('#detail-article-valorisation').text(formatMontant(article.quantite_stock * article.prix_achat) + ' FCFA');

                    const $mouvements = $('#detail-article-mouvements').empty();
                    if (reponse.mouvements.length === 0) {
                        $mouvements.append('<tr><td colspan="3" class="text-center text-muted py-3">Aucun mouvement.</td></tr>');
                    }
                    reponse.mouvements.forEach(function (mouvement) {
                        const cle = mouvement.type === 'entree' ? 'entree' : ('sortie_' + mouvement.nature);
                        const info = TYPES_MOUVEMENT[cle] ?? { libelle: mouvement.type, classe: 'bg-secondary' };
                        $mouvements.append(`
                            <tr>
                                <td>${new Date(mouvement.date_mouvement).toLocaleDateString('fr-FR')}</td>
                                <td><span class="badge ${info.classe}">${info.libelle}</span></td>
                                <td class="text-end">${mouvement.quantite}</td>
                            </tr>
                        `);
                    });

                    modalDetailArticle.show();
                });
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresEtatStock());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-etat-stock').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.etat-stock.export.excel') }}');
            });

            $('#btn-export-pdf-etat-stock').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.etat-stock.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
