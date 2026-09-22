<x-app-layout>
    <x-slot name="header">Sorties de stock</x-slot>

    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-primary">Interne</span>
        <span class="small text-muted" id="kpi-periode-label-interne">Période : mois en cours</span>
    </div>
    <div class="row g-3 mb-3 row-cols-1 row-cols-sm-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Sorties du jour</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-jour-interne">{{ $kpis['sorties_jour_interne'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Quantité sortie</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-quantite-interne">{{ $kpis['quantite_interne'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Valeur</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-valeur-interne">{{ \App\Support\Money::format($kpis['valeur_interne']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-info text-dark">Vente externe</span>
        <span class="small text-muted" id="kpi-periode-label-externe">Période : mois en cours</span>
    </div>
    <div class="row g-3 mb-3 row-cols-1 row-cols-sm-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Ventes du jour</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-jour-externe">{{ $kpis['sorties_jour_externe'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Quantité vendue</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-quantite-externe">{{ $kpis['quantite_externe'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Valeur des ventes</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-valeur-externe">{{ \App\Support\Money::format($kpis['valeur_externe']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-3">
        @can('sortieInterne', \App\Models\SortieStock::class)
            <button type="button" class="btn btn-outline-primary" id="btn-sortie-interne">
                <i class="bi bi-truck me-1"></i>Sortie interne
            </button>
        @endcan
        @can('sortieVente', \App\Models\SortieStock::class)
            <button type="button" class="btn btn-primary" id="btn-sortie-externe">
                <i class="bi bi-cash-coin me-1"></i>Vente externe
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-sorties" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Article</label>
                    <select name="article_id" class="form-select form-select-sm select2-filtre-article">
                        <option value="">Tous</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}">{{ $article->reference }} — {{ $article->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Nature</label>
                    <select name="nature" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <option value="interne">Interne</option>
                        <option value="externe">Vente externe</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-sorties">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-sorties">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="sorties" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-sorties">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Nature</th>
                        <th class="text-end">Articles</th>
                        <th class="text-end">Quantité</th>
                        <th>Destination</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end" style="width: 60px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale nouvelle sortie --}}
    <div class="modal fade" id="modal-sortie" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-sortie">
                    <input type="hidden" name="nature" id="sortie-nature">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-sortie-titre">Sortie de stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Motif</label>
                                <input type="text" name="motif" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control" placeholder="Générée automatiquement si vide">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_sortie" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="champs-interne row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Véhicule</label>
                                <select name="vehicule_id" class="form-select select2-vehicule">
                                    <option value=""></option>
                                    @foreach ($vehicules as $vehicule)
                                        <option value="{{ $vehicule->id }}">{{ $vehicule->code }} — {{ $vehicule->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="champs-externe row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Véhicule externe (immatriculation)</label>
                                <input type="text" name="vehicule_externe" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acheteur</label>
                                <input type="text" name="acheteur" class="form-control">
                            </div>
                        </div>

                        <hr>

                        <div id="lignes-sortie"></div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-ajouter-ligne-sortie">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter un article
                        </button>

                        <hr>

                        <div class="row justify-content-end champs-externe">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total</span>
                                    <strong id="sortie-total">0 FCFA</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer la sortie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Gabarit d'une ligne de sortie --}}
    <template id="gabarit-ligne-sortie">
        <div class="row align-items-end ligne-sortie mb-2">
            <div class="col-12 col-md-6">
                <label class="form-label small">Article</label>
                <select name="lignes[__index__][article_id]" class="form-select select2-article-sortie" required>
                    <option value=""></option>
                    @foreach ($articles as $article)
                        <option value="{{ $article->id }}" data-stock="{{ $article->quantite_stock }}">{{ $article->reference }} — {{ $article->nom }} (stock : {{ $article->quantite_stock }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Quantité</label>
                <div class="input-group">
                    <button type="button" class="btn btn-outline-secondary btn-quantite-moins" tabindex="-1">−</button>
                    <input type="number" name="lignes[__index__][quantite]" class="form-control text-center ligne-quantite" min="1" value="1" required>
                    <button type="button" class="btn btn-outline-secondary btn-quantite-plus" tabindex="-1">+</button>
                </div>
            </div>
            <div class="col-5 col-md-2 champ-prix-vente-ligne d-none">
                <label class="form-label small">Prix de vente</label>
                <input type="number" name="lignes[__index__][prix_vente]" class="form-control ligne-prix-vente" min="0" step="0.01">
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-outline-danger btn-supprimer-ligne-sortie"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    {{-- Modale détail --}}
    <div class="modal fade" id="modal-detail-sortie" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="detail-sortie-reference">—</h5>
                        <span id="detail-sortie-nature"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="small text-muted">Date</div>
                            <div class="fw-semibold" id="detail-sortie-date">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Destination</div>
                            <div class="fw-semibold" id="detail-sortie-destination">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Motif</div>
                            <div class="fw-semibold" id="detail-sortie-motif">—</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th class="text-end">Quantité</th>
                                    <th class="text-end" id="detail-sortie-entete-prix">Prix unitaire</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody id="detail-sortie-lignes"></tbody>
                        </table>
                    </div>

                    <div class="row g-3 justify-content-end text-end">
                        <div class="col-sm-4">
                            <div class="small text-muted">Montant total</div>
                            <div class="fw-semibold" id="detail-sortie-total">—</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" id="detail-sortie-imprimer" href="#" target="_blank"><i class="bi bi-printer me-2"></i>Imprimer</a></li>
                            <li><a class="dropdown-item" id="detail-sortie-pdf" href="#"><i class="bi bi-file-earmark-pdf me-2"></i>Télécharger PDF</a></li>
                            <li><a class="dropdown-item" id="detail-sortie-excel" href="#"><i class="bi bi-file-earmark-excel me-2"></i>Télécharger Excel</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            let ligneIndex = 0;
            const modal = new bootstrap.Modal('#modal-sortie');
            const modalDetail = new bootstrap.Modal('#modal-detail-sortie');

            function recalculerTotalSortie() {
                let total = 0;
                $('.ligne-sortie').each(function () {
                    const q = parseFloat($(this).find('.ligne-quantite').val()) || 0;
                    const p = parseFloat($(this).find('.ligne-prix-vente').val()) || 0;
                    total += q * p;
                });
                $('#sortie-total').text(formatMontant(total) + ' FCFA');
            }

            // Un même article ne doit pas pouvoir être choisi sur deux lignes différentes.
            function actualiserOptionsArticlesDisponiblesSortie() {
                const selectionnes = $('.select2-article-sortie').map(function () { return $(this).val(); }).get().filter(Boolean);

                $('.select2-article-sortie').each(function () {
                    const valeurActuelle = $(this).val();
                    $(this).find('option').each(function () {
                        $(this).prop('disabled', this.value !== '' && this.value !== valeurActuelle && selectionnes.includes(this.value));
                    });
                });
            }

            function ajouterLigneSortie() {
                const html = $('#gabarit-ligne-sortie').html().replaceAll('__index__', ligneIndex++);
                const $ligne = $(html);
                $('#lignes-sortie').append($ligne);
                $ligne.find('.select2-article-sortie').select2({ dropdownParent: $('#modal-sortie'), width: '100%' });

                if ($('#sortie-nature').val() === 'externe') {
                    $ligne.find('.champ-prix-vente-ligne').removeClass('d-none');
                    $ligne.find('.ligne-prix-vente').prop('required', true);
                }

                actualiserOptionsArticlesDisponiblesSortie();

                return $ligne;
            }

            $('#btn-ajouter-ligne-sortie').on('click', () => ajouterLigneSortie());

            $('#lignes-sortie').on('click', '.btn-quantite-plus', function () {
                const $input = $(this).closest('.input-group').find('.ligne-quantite');
                const max = parseInt($input.attr('max'), 10);
                const prochaine = (parseInt($input.val(), 10) || 0) + 1;
                $input.val(max && prochaine > max ? max : prochaine).trigger('input');
            });

            $('#lignes-sortie').on('click', '.btn-quantite-moins', function () {
                const $input = $(this).closest('.input-group').find('.ligne-quantite');
                $input.val(Math.max(1, (parseInt($input.val(), 10) || 0) - 1)).trigger('input');
            });

            // La quantité ne doit jamais pouvoir dépasser le stock disponible de l'article choisi.
            function actualiserMaxQuantite($select) {
                const $quantite = $select.closest('.ligne-sortie').find('.ligne-quantite');
                const stock = parseInt($select.find(':selected').data('stock'), 10) || 0;
                $quantite.attr('max', stock);

                if (stock > 0 && parseInt($quantite.val(), 10) > stock) {
                    $quantite.val(stock);
                }
            }

            $('#lignes-sortie').on('change', '.select2-article-sortie', function () {
                actualiserOptionsArticlesDisponiblesSortie();
                actualiserMaxQuantite($(this));
            });

            $('#lignes-sortie').on('input', '.ligne-quantite, .ligne-prix-vente', function () {
                if ($(this).hasClass('ligne-quantite')) {
                    const max = parseInt($(this).attr('max'), 10);
                    if (max && parseInt($(this).val(), 10) > max) {
                        $(this).val(max);
                    }
                }

                recalculerTotalSortie();
            });

            $('#lignes-sortie').on('click', '.btn-supprimer-ligne-sortie', function () {
                $(this).closest('.ligne-sortie').remove();
                actualiserOptionsArticlesDisponiblesSortie();
                recalculerTotalSortie();
            });

            function ouvrirModale(nature) {
                $('#form-sortie')[0].reset();
                $('#sortie-nature').val(nature);
                $('.select2-vehicule').val('').trigger('change');
                $('#lignes-sortie').empty();
                ligneIndex = 0;

                if (nature === 'interne') {
                    $('#modal-sortie-titre').text('Sortie interne (véhicule du parc)');
                    $('.champs-interne').show();
                    $('.champs-externe').hide();
                    $('[name=vehicule_id]').prop('required', true);
                    $('[name=vehicule_externe], [name=acheteur]').prop('required', false);
                } else {
                    $('#modal-sortie-titre').text('Vente externe');
                    $('.champs-interne').hide();
                    $('.champs-externe').show();
                    $('[name=vehicule_id]').prop('required', false);
                    $('[name=vehicule_externe], [name=acheteur]').prop('required', true);
                }

                ajouterLigneSortie();
                recalculerTotalSortie();
                modal.show();
            }

            $('#btn-sortie-interne').on('click', () => ouvrirModale('interne'));
            $('#btn-sortie-externe').on('click', () => ouvrirModale('externe'));

            $('.select2-vehicule').select2({ dropdownParent: $('#modal-sortie'), width: '100%' });
            $('.select2-filtre-article').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            function libellePeriodeKpis() {
                const f = filtresSorties();

                if (!f.date_debut && !f.date_fin) {
                    return 'Période : mois en cours';
                }

                const du = f.date_debut ? new Date(f.date_debut).toLocaleDateString('fr-FR') : '…';
                const au = f.date_fin ? new Date(f.date_fin).toLocaleDateString('fr-FR') : '…';

                return `Période : du ${du} au ${au}`;
            }

            function rafraichirKpisSorties() {
                $.get('{{ route('stock.sorties.kpis') }}', filtresSorties(), function (kpis) {
                    $('#kpi-jour-interne').text(kpis.sorties_jour_interne);
                    $('#kpi-quantite-interne').text(kpis.quantite_interne);
                    $('#kpi-valeur-interne').text(formatMontant(kpis.valeur_interne) + ' FCFA');
                    $('#kpi-jour-externe').text(kpis.sorties_jour_externe);
                    $('#kpi-quantite-externe').text(kpis.quantite_externe);
                    $('#kpi-valeur-externe').text(formatMontant(kpis.valeur_externe) + ' FCFA');

                    const libelle = libellePeriodeKpis();
                    $('#kpi-periode-label-interne, #kpi-periode-label-externe').text(libelle);
                });
            }

            $('#form-sortie').on('submit', function (e) {
                e.preventDefault();

                if ($('.ligne-sortie').length === 0) {
                    Swal.fire({ icon: 'warning', text: 'Ajoutez au moins un article.' });
                    return;
                }

                $.post('/stock/sorties', $(this).serialize())
                    .done(function (res) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-sorties').DataTable().ajax.reload();
                        rafraichirKpisSorties();
                    })
                    .fail(function (xhr) {
                        const data = xhr.responseJSON;
                        if (data?.insufficient_stock) {
                            Swal.fire({ icon: 'warning', title: 'Stock insuffisant', text: data.message });
                        } else {
                            const erreurs = data?.errors;
                            const msg = erreurs ? Object.values(erreurs).flat()[0] : (data?.message || 'Une erreur est survenue.');
                            Swal.fire({ icon: 'error', text: msg });
                        }
                    });
            });

            function filtresSorties() {
                return {
                    date_debut: $('#filtres-sorties [name=date_debut]').val(),
                    date_fin: $('#filtres-sorties [name=date_fin]').val(),
                    article_id: $('#filtres-sorties [name=article_id]').val(),
                    nature: $('#filtres-sorties [name=nature]').val(),
                };
            }

            function actualiserBoutonResetSorties() {
                const actif = Object.values(filtresSorties()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-sorties').toggleClass('d-none', !actif);
            }

            $('#filtres-sorties').on('change input', actualiserBoutonResetSorties);
            actualiserBoutonResetSorties();

            const tableSorties = $('#table-sorties').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.sorties.data') }}',
                    data: (d) => Object.assign(d, filtresSorties()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_sortie', name: 'date_sortie' },
                    { data: 'reference', name: 'reference' },
                    { data: 'nature_badge', name: 'nature', orderable: false },
                    { data: 'lignes_count', name: 'lignes_count', className: 'text-end', orderable: false },
                    { data: 'quantite_totale', name: 'quantite_totale', className: 'text-end', orderable: false },
                    { data: 'destination', name: 'destination', orderable: false },
                    { data: 'montant_total', name: 'montant_total', className: 'text-end' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (sortie) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-sortie" data-id="${sortie.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-sorties').on('click', function () {
                tableSorties.ajax.reload();
                rafraichirKpisSorties();
            });

            $('#btn-reset-sorties').on('click', function () {
                $('#filtres-sorties')[0].reset();
                $('.select2-filtre-article').val('').trigger('change');
                tableSorties.ajax.reload();
                rafraichirKpisSorties();
            });

            $('#table-sorties').on('click', '.btn-detail-sortie', function () {
                const id = $(this).data('id');

                $.get(`/stock/sorties/${id}`, function (sortie) {
                    const estExterne = sortie.nature === 'externe';

                    $('#detail-sortie-reference').text(sortie.reference);
                    $('#detail-sortie-nature').html(estExterne
                        ? '<span class="badge bg-info text-dark">Vente externe</span>'
                        : '<span class="badge bg-primary">Interne</span>');
                    $('#detail-sortie-date').text(new Date(sortie.date_sortie).toLocaleDateString('fr-FR'));
                    $('#detail-sortie-destination').text(estExterne
                        ? [sortie.vehicule_externe, sortie.acheteur].filter(Boolean).join(' — ')
                        : (sortie.vehicule_code ?? '—'));
                    $('#detail-sortie-motif').text(sortie.motif || '—');
                    $('#detail-sortie-entete-prix').text(estExterne ? 'Prix de vente' : 'Valorisation unitaire');

                    const $lignes = $('#detail-sortie-lignes').empty();
                    sortie.lignes.forEach(function (ligne) {
                        const prix = ligne.prix_vente ?? ligne.prix_unitaire;
                        $lignes.append(`
                            <tr>
                                <td>${ligne.article_reference} — ${ligne.article_nom}</td>
                                <td class="text-end">${ligne.quantite}</td>
                                <td class="text-end">${formatMontant(prix)} FCFA</td>
                                <td class="text-end">${formatMontant(ligne.montant)} FCFA</td>
                            </tr>
                        `);
                    });

                    $('#detail-sortie-total').text(formatMontant(sortie.montant_total) + ' FCFA');

                    $('#detail-sortie-imprimer').attr('href', `/stock/sorties/${sortie.id}/pdf`);
                    $('#detail-sortie-pdf').attr('href', `/stock/sorties/${sortie.id}/pdf?download=1`);
                    $('#detail-sortie-excel').attr('href', `/stock/sorties/${sortie.id}/excel`);

                    modalDetail.show();
                });
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresSorties());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-sorties').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.sorties.export.excel') }}');
            });

            $('#btn-export-pdf-sorties').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.sorties.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
