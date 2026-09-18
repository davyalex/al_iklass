<x-app-layout>
    <x-slot name="header">Achats</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\Achat::class)
            <button type="button" class="btn btn-primary" id="btn-nouvel-achat">
                <i class="bi bi-plus-lg me-1"></i>Nouvel achat
            </button>
        @endcan
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted">Total achats (période)</span>
                        <span class="badge rounded-pill bg-primary" id="kpi-total-count" title="Nombre d'achats sur la période filtrée">{{ $kpiPeriode['count'] }}</span>
                    </div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-total-montant">{{ \App\Support\Money::format($kpiPeriode['total']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted">Achats du mois</span>
                        <span class="badge rounded-pill bg-primary" id="kpi-mois-count" title="Nombre d'achats ce mois-ci">{{ $kpiMois['mois_count'] }}</span>
                    </div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-mois-montant">{{ \App\Support\Money::format($kpiMois['mois']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Déjà payé (période)</div>
                    <div class="h5 mb-0 text-success" id="kpi-paye-montant">{{ \App\Support\Money::format($kpiPeriode['paye']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Solde dû / restant (période)</div>
                    <div class="h5 mb-0 text-danger" id="kpi-restant-montant">{{ \App\Support\Money::format($kpiPeriode['restant']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>
    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i>« Total achats », « Déjà payé » et « Solde dû / restant » suivent le filtre ci-dessous (toute la période par défaut). Seul « Achats du mois » reste fixe sur le mois en cours.
    </p>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-achats" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Fournisseur</label>
                    <select name="fournisseur_id" class="form-select form-select-sm select2-filtre-fournisseur">
                        <option value="">Tous</option>
                        @foreach ($fournisseurs as $fournisseur)
                            <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut_paiement" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="comptant">Comptant</option>
                        <option value="partiel">Partiel</option>
                        <option value="credit">Crédit</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-achats">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-achats">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="achats" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-achats">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Fournisseur</th>
                        <th>Total</th>
                        <th>Payé</th>
                        <th>Restant</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 70px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale nouvel achat --}}
    <div class="modal fade" id="modal-achat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="form-achat">
                    <input type="hidden" name="bon_commande_id" id="achat-bon-commande-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-achat-titre">Nouvel achat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small d-none" id="achat-info-bc"></div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <select name="fournisseur_id" class="form-select select2-fournisseur" required>
                                    <option value=""></option>
                                    @foreach ($fournisseurs as $fournisseur)
                                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control" placeholder="Générée automatiquement si vide">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_achat" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <hr>

                        <div id="lignes-achat"></div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-ajouter-ligne">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter une ligne
                        </button>

                        <hr>

                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total</span>
                                    <strong id="achat-total">0 FCFA</strong>
                                </div>
                                <label class="form-label">Montant déjà payé</label>
                                <input type="number" name="montant_paye" id="achat-montant-paye" class="form-control" min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'achat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Gabarit d'une ligne d'achat --}}
    <template id="gabarit-ligne-achat">
        <div class="row align-items-end ligne-achat mb-2">
            <input type="hidden" name="lignes[__index__][bon_commande_ligne_id]" class="ligne-bon-commande-ligne-id">
            <div class="col-12 col-md-5">
                <label class="form-label small">Article</label>
                <select name="lignes[__index__][article_id]" class="form-select select2-article" required>
                    <option value=""></option>
                    @foreach ($articles as $article)
                        <option value="{{ $article->id }}" data-prix="{{ $article->prix_achat }}">{{ $article->reference }} — {{ $article->nom }}</option>
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
            <div class="col-5 col-md-3">
                <label class="form-label small">Prix unitaire</label>
                <input type="number" name="lignes[__index__][prix_unitaire]" class="form-control ligne-prix" min="0" step="0.01" required>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-outline-danger btn-supprimer-ligne"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    {{-- Modale détail --}}
    <div class="modal fade" id="modal-detail-achat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="detail-achat-reference">—</h5>
                        <span id="detail-achat-statut"></span>
                        <span id="detail-achat-bc-badge" class="d-none"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="small text-muted">Fournisseur</div>
                            <div class="fw-semibold" id="detail-achat-fournisseur">—</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted">Date</div>
                            <div class="fw-semibold" id="detail-achat-date">—</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th class="text-end">Quantité</th>
                                    <th class="text-end">Prix unitaire</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody id="detail-achat-lignes"></tbody>
                        </table>
                    </div>

                    <div class="row g-3 justify-content-end text-end">
                        <div class="col-sm-4">
                            <div class="small text-muted">Total</div>
                            <div class="fw-semibold" id="detail-achat-total">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Payé</div>
                            <div class="fw-semibold" id="detail-achat-paye">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Restant dû</div>
                            <div class="fw-semibold" id="detail-achat-restant">—</div>
                        </div>
                    </div>

                    <div id="detail-achat-commentaire-bloc" class="d-none mt-3">
                        <div class="small text-muted">Commentaire</div>
                        <div id="detail-achat-commentaire"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" id="detail-achat-imprimer" href="#" target="_blank"><i class="bi bi-printer me-2"></i>Imprimer</a></li>
                            <li><a class="dropdown-item" id="detail-achat-pdf" href="#"><i class="bi bi-file-earmark-pdf me-2"></i>Télécharger PDF</a></li>
                            <li><a class="dropdown-item" id="detail-achat-excel" href="#"><i class="bi bi-file-earmark-excel me-2"></i>Télécharger Excel</a></li>
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
            const modalAchat = new bootstrap.Modal('#modal-achat');
            const modalDetailAchat = new bootstrap.Modal('#modal-detail-achat');

            const STATUTS_PAIEMENT = {
                comptant: { libelle: 'Comptant', classe: 'bg-success' },
                partiel: { libelle: 'Partiel', classe: 'bg-warning text-dark' },
                credit: { libelle: 'Crédit', classe: 'bg-danger' },
            };

            $('.select2-fournisseur').select2({ dropdownParent: $('#modal-achat'), width: '100%' });
            $('.select2-filtre-fournisseur').select2({ width: '100%', placeholder: 'Tous', containerCssClass: 'select2-sm' });

            function recalculerTotal() {
                let total = 0;
                $('.ligne-achat').each(function () {
                    const q = parseFloat($(this).find('.ligne-quantite').val()) || 0;
                    const p = parseFloat($(this).find('.ligne-prix').val()) || 0;
                    total += q * p;
                });
                $('#achat-total').text(formatMontant(total) + ' FCFA');
            }

            // Un même article ne doit pas pouvoir être choisi sur deux lignes différentes :
            // on grise (disabled) ses options dans tous les autres select2-article.
            function actualiserOptionsArticlesDisponibles() {
                const selectionnes = $('.select2-article').map(function () { return $(this).val(); }).get().filter(Boolean);

                $('.select2-article').each(function () {
                    const valeurActuelle = $(this).val();
                    $(this).find('option').each(function () {
                        $(this).prop('disabled', this.value !== '' && this.value !== valeurActuelle && selectionnes.includes(this.value));
                    });
                });
            }

            function ajouterLigne(prefill) {
                const html = $('#gabarit-ligne-achat').html().replaceAll('__index__', ligneIndex++);
                const $ligne = $(html);
                $('#lignes-achat').append($ligne);
                $ligne.find('.select2-article').select2({ dropdownParent: $('#modal-achat'), width: '100%' });

                if (prefill) {
                    $ligne.find('.ligne-bon-commande-ligne-id').val(prefill.bon_commande_ligne_id);
                    $ligne.find('.select2-article').val(prefill.article_id).trigger('change');
                    $ligne.find('.ligne-quantite').val(prefill.quantite);
                    $ligne.find('.ligne-prix').val(prefill.prix_unitaire);

                    if (prefill.bon_commande_ligne_id) {
                        // Ligne issue d'un bon de commande : on ne peut recevoir que l'article
                        // commandé, et jamais plus que la quantité restant à recevoir. Le select
                        // reste "enabled" (un select disabled n'est pas envoyé par serialize())
                        // mais son ouverture est bloquée pour empêcher de changer l'article.
                        $ligne.find('.select2-article')
                            .prop('disabled', false)
                            .on('select2:opening', (e) => e.preventDefault());
                        $ligne.find('.select2-container').css({ opacity: 0.65, cursor: 'not-allowed' });
                        $ligne.find('.ligne-quantite').attr('max', prefill.restant);
                        $ligne.find('.btn-supprimer-ligne').attr('title', "Ne rien recevoir pour cet article cette fois-ci");
                    }
                }

                actualiserOptionsArticlesDisponibles();

                return $ligne;
            }

            $('#btn-ajouter-ligne').on('click', () => ajouterLigne());

            $('#lignes-achat').on('input', '.ligne-quantite', function () {
                const max = parseInt($(this).attr('max'), 10);
                if (max && parseInt($(this).val(), 10) > max) {
                    $(this).val(max);
                }
            });

            $('#lignes-achat').on('click', '.btn-quantite-plus', function () {
                const $input = $(this).closest('.input-group').find('.ligne-quantite');
                const max = parseInt($input.attr('max'), 10);
                let valeur = (parseInt($input.val(), 10) || 0) + 1;
                if (max && valeur > max) valeur = max;
                $input.val(valeur).trigger('input');
            });

            $('#lignes-achat').on('click', '.btn-quantite-moins', function () {
                const $input = $(this).closest('.input-group').find('.ligne-quantite');
                const valeur = Math.max(1, (parseInt($input.val(), 10) || 0) - 1);
                $input.val(valeur).trigger('input');
            });

            // Réception depuis un bon de commande (arrivée via /stock/achats?bon_commande_id=X)
            const bonCommandeId = new URLSearchParams(window.location.search).get('bon_commande_id');
            if (bonCommandeId) {
                $.get(`/stock/bons-commande/${bonCommandeId}`, function (bc) {
                    $('#form-achat')[0].reset();
                    $('#lignes-achat').empty();
                    ligneIndex = 0;

                    $('#modal-achat-titre').text('Réception — bon de commande #' + bc.id);
                    $('#achat-bon-commande-id').val(bc.id);
                    $('#achat-info-bc').removeClass('d-none').text(
                        'Cette réception va mettre à jour le bon de commande #' + bc.id + '. Ajustez les quantités si la livraison est partielle.'
                    );
                    $('.select2-fournisseur').val(bc.fournisseur_id).trigger('change');

                    bc.lignes.forEach(function (ligne) {
                        const restant = ligne.quantite_commandee - ligne.quantite_recue;
                        if (restant <= 0) {
                            return;
                        }
                        ajouterLigne({
                            bon_commande_ligne_id: ligne.id,
                            article_id: ligne.article_id,
                            quantite: restant,
                            prix_unitaire: ligne.prix_unitaire_estime,
                            restant: restant,
                        });
                    });

                    // Une réception liée à un bon de commande ne peut porter que sur les
                    // articles commandés : impossible d'en ajouter d'autres.
                    $('#btn-ajouter-ligne').addClass('d-none');

                    recalculerTotal();
                    modalAchat.show();

                    // Nettoie l'URL pour éviter de rouvrir la modale si l'utilisateur rafraîchit la page.
                    window.history.replaceState({}, '', '/stock/achats');
                });
            }

            $('#lignes-achat').on('change', '.select2-article', function () {
                // Ne préremplir que si l'article a déjà un prix d'achat connu (achat
                // précédent) : sinon prix_achat vaut 0 par défaut et on laisse le champ
                // vide pour forcer une saisie plutôt que de suggérer un prix à 0.
                const prix = parseFloat($(this).find(':selected').data('prix'));
                if (prix > 0) {
                    $(this).closest('.ligne-achat').find('.ligne-prix').val(prix);
                }
                actualiserOptionsArticlesDisponibles();
                recalculerTotal();
            });

            $('#lignes-achat').on('input', '.ligne-quantite, .ligne-prix', recalculerTotal);

            $('#lignes-achat').on('click', '.btn-supprimer-ligne', function () {
                $(this).closest('.ligne-achat').remove();
                actualiserOptionsArticlesDisponibles();
                recalculerTotal();
            });

            $('#btn-nouvel-achat').on('click', function () {
                $('#form-achat')[0].reset();
                $('#lignes-achat').empty();
                ligneIndex = 0;
                $('#modal-achat-titre').text('Nouvel achat');
                $('#achat-bon-commande-id').val('');
                $('#achat-info-bc').addClass('d-none').text('');
                $('.select2-fournisseur').val('').trigger('change');
                $('#btn-ajouter-ligne').removeClass('d-none');
                ajouterLigne();
                recalculerTotal();
                modalAchat.show();
            });

            $('#form-achat').on('submit', function (e) {
                e.preventDefault();

                if ($('.ligne-achat').length === 0) {
                    Swal.fire({ icon: 'warning', text: 'Ajoutez au moins une ligne.' });
                    return;
                }

                $.post('/stock/achats', $(this).serialize())
                    .done(function (res) {
                        modalAchat.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-achats').DataTable().ajax.reload();
                        rafraichirKpisPeriode();
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            function filtresAchats() {
                return {
                    date_debut: $('#filtres-achats [name=date_debut]').val(),
                    date_fin: $('#filtres-achats [name=date_fin]').val(),
                    fournisseur_id: $('#filtres-achats [name=fournisseur_id]').val(),
                    statut_paiement: $('#filtres-achats [name=statut_paiement]').val(),
                };
            }

            const tableAchats = $('#table-achats').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.achats.data') }}',
                    data: (d) => Object.assign(d, filtresAchats()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_achat', name: 'date_achat' },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'fournisseur_nom', name: 'fournisseur_nom' },
                    { data: 'montant_total', name: 'montant_total' },
                    { data: 'montant_paye', name: 'montant_paye' },
                    { data: 'montant_restant', name: 'montant_restant' },
                    { data: 'statut_badge', name: 'statut_paiement', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (achat) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-achat" data-id="${achat.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[0, 'desc']],
            });

            $('#table-achats').on('click', '.btn-detail-achat', function () {
                const id = $(this).data('id');

                $.get(`/stock/achats/${id}`, function (achat) {
                    const statutInfo = STATUTS_PAIEMENT[achat.statut_paiement] ?? { libelle: achat.statut_paiement, classe: 'bg-secondary' };

                    $('#detail-achat-reference').text(achat.reference ?? ('Achat #' + achat.id));
                    $('#detail-achat-statut').html(`<span class="badge ${statutInfo.classe}">${statutInfo.libelle}</span>`);
                    if (achat.bon_commande) {
                        $('#detail-achat-bc-badge').removeClass('d-none')
                            .html(`<span class="badge bg-light text-dark border ms-1">BC : ${achat.bon_commande.reference}</span>`);
                    } else {
                        $('#detail-achat-bc-badge').addClass('d-none').empty();
                    }
                    $('#detail-achat-fournisseur').text(achat.fournisseur_nom);
                    $('#detail-achat-date').text(new Date(achat.date_achat).toLocaleDateString('fr-FR'));

                    const $lignes = $('#detail-achat-lignes').empty();
                    achat.lignes.forEach(function (ligne) {
                        $lignes.append(`
                            <tr>
                                <td>${ligne.article_reference} — ${ligne.article_nom}</td>
                                <td class="text-end">${ligne.quantite}</td>
                                <td class="text-end">${formatMontant(ligne.prix_unitaire)} FCFA</td>
                                <td class="text-end">${formatMontant(ligne.montant)} FCFA</td>
                            </tr>
                        `);
                    });

                    $('#detail-achat-total').text(formatMontant(achat.montant_total) + ' FCFA');
                    $('#detail-achat-paye').text(formatMontant(achat.montant_paye) + ' FCFA');
                    $('#detail-achat-restant').text(formatMontant(achat.montant_restant) + ' FCFA');

                    if (achat.commentaire) {
                        $('#detail-achat-commentaire-bloc').removeClass('d-none');
                        $('#detail-achat-commentaire').text(achat.commentaire);
                    } else {
                        $('#detail-achat-commentaire-bloc').addClass('d-none');
                    }

                    $('#detail-achat-imprimer').attr('href', `/stock/achats/${achat.id}/pdf`);
                    $('#detail-achat-pdf').attr('href', `/stock/achats/${achat.id}/pdf?download=1`);
                    $('#detail-achat-excel').attr('href', `/stock/achats/${achat.id}/excel`);

                    modalDetailAchat.show();
                });
            });

            function rafraichirKpisPeriode() {
                $.get('{{ route('stock.achats.kpis') }}', filtresAchats(), function (kpis) {
                    $('#kpi-total-count').text(kpis.count);
                    $('#kpi-total-montant').text(formatMontant(kpis.total) + ' FCFA');
                    $('#kpi-paye-montant').text(formatMontant(kpis.paye) + ' FCFA');
                    $('#kpi-restant-montant').text(formatMontant(kpis.restant) + ' FCFA');
                    $('#kpi-mois-count').text(kpis.mois_count);
                    $('#kpi-mois-montant').text(formatMontant(kpis.mois) + ' FCFA');
                });
            }

            $('#btn-filtrer-achats').on('click', function () {
                tableAchats.ajax.reload();
                rafraichirKpisPeriode();
            });

            $('#btn-reset-achats').on('click', function () {
                $('#filtres-achats')[0].reset();
                $('.select2-filtre-fournisseur').val('').trigger('change');
                tableAchats.ajax.reload();
                rafraichirKpisPeriode();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresAchats());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-achats').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.achats.export.excel') }}');
            });

            $('#btn-export-pdf-achats').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.achats.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
