<x-app-layout>
    <x-slot name="header">Bons de commande</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\BonCommande::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-bon-commande">
                <i class="bi bi-plus-lg me-1"></i>Nouveau bon de commande
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-bons-commande">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Fournisseur</th>
                        <th class="text-center">Réception</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 70px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale création --}}
    <div class="modal fade" id="modal-bon-commande" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="form-bon-commande">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau bon de commande</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <select name="fournisseur_id" class="form-select select2-fournisseur-bc" required>
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
                                <input type="date" name="date_commande" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <hr>

                        <div id="lignes-bon-commande"></div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-ajouter-ligne-bc">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter une ligne
                        </button>

                        <hr>

                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total estimé</span>
                                    <strong id="bc-total">0 FCFA</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le bon de commande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Gabarit d'une ligne de bon de commande (formulaire de création) --}}
    <template id="gabarit-ligne-bc">
        <div class="row align-items-end ligne-bc mb-2">
            <div class="col-12 col-md-5">
                <label class="form-label small">Article</label>
                <select name="lignes[__index__][article_id]" class="form-select select2-article-bc" required>
                    <option value=""></option>
                    @foreach ($articles as $article)
                        <option value="{{ $article->id }}" data-prix="{{ $article->prix_achat }}">{{ $article->reference }} — {{ $article->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Quantité</label>
                <div class="input-group">
                    <button type="button" class="btn btn-outline-secondary btn-quantite-bc-moins" tabindex="-1">−</button>
                    <input type="number" name="lignes[__index__][quantite_commandee]" class="form-control text-center ligne-bc-quantite" min="1" value="1" required>
                    <button type="button" class="btn btn-outline-secondary btn-quantite-bc-plus" tabindex="-1">+</button>
                </div>
            </div>
            <div class="col-5 col-md-3">
                <label class="form-label small">Prix unitaire estimé</label>
                <input type="number" name="lignes[__index__][prix_unitaire_estime]" class="form-control ligne-bc-prix" min="0" step="0.01" required>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-outline-danger btn-supprimer-ligne-bc"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    {{-- Modale détail --}}
    <div class="modal fade" id="modal-detail-bc" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="detail-bc-reference">—</h5>
                        <span id="detail-bc-statut"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="small text-muted">Fournisseur</div>
                            <div class="fw-semibold" id="detail-bc-fournisseur">—</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted">Date de commande</div>
                            <div class="fw-semibold" id="detail-bc-date">—</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Avancement de la réception</span>
                            <span id="detail-bc-taux">0 / 0</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" id="detail-bc-progress" role="progressbar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th class="text-end">Commandé</th>
                                    <th class="text-end">Reçu</th>
                                    <th class="text-end">Prix estimé</th>
                                    <th class="text-end">Montant estimé</th>
                                </tr>
                            </thead>
                            <tbody id="detail-bc-lignes"></tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td colspan="4">Total estimé</td>
                                    <td class="text-end" id="detail-bc-total">0 FCFA</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div id="detail-bc-commentaire-bloc" class="d-none">
                        <div class="small text-muted">Commentaire</div>
                        <div id="detail-bc-commentaire"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between flex-wrap gap-2">
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" id="detail-bc-imprimer" href="#" target="_blank"><i class="bi bi-printer me-2"></i>Imprimer</a></li>
                            <li><a class="dropdown-item" id="detail-bc-pdf" href="#"><i class="bi bi-file-earmark-pdf me-2"></i>Télécharger PDF</a></li>
                            <li><a class="dropdown-item" id="detail-bc-excel" href="#"><i class="bi bi-file-earmark-excel me-2"></i>Télécharger Excel</a></li>
                        </ul>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-danger d-none" id="detail-bc-supprimer">
                            <i class="bi bi-trash me-1"></i>Supprimer
                        </button>
                        <button type="button" class="btn btn-outline-danger d-none" id="detail-bc-annuler">
                            Annuler le bon
                        </button>
                        <a href="#" class="btn btn-primary d-none" id="detail-bc-recevoir">
                            Recevoir
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            let ligneIndex = 0;
            const modalBc = new bootstrap.Modal('#modal-bon-commande');
            const modalDetail = new bootstrap.Modal('#modal-detail-bc');

            const STATUTS = {
                en_attente: { libelle: 'En attente', classe: 'bg-secondary' },
                partiellement_recu: { libelle: 'Partiellement reçu', classe: 'bg-warning text-dark' },
                recu: { libelle: 'Reçu', classe: 'bg-success' },
                annule: { libelle: 'Annulé', classe: 'bg-danger' },
            };

            $('.select2-fournisseur-bc').select2({ dropdownParent: $('#modal-bon-commande'), width: '100%' });

            function recalculerTotalBc() {
                let total = 0;
                $('.ligne-bc').each(function () {
                    const q = parseFloat($(this).find('.ligne-bc-quantite').val()) || 0;
                    const p = parseFloat($(this).find('.ligne-bc-prix').val()) || 0;
                    total += q * p;
                });
                $('#bc-total').text(formatMontant(total) + ' FCFA');
            }

            // Un même article ne doit pas pouvoir être choisi sur deux lignes différentes :
            // on grise (disabled) ses options dans tous les autres select2-article-bc.
            function actualiserOptionsArticlesDisponiblesBc() {
                const selectionnes = $('.select2-article-bc').map(function () { return $(this).val(); }).get().filter(Boolean);

                $('.select2-article-bc').each(function () {
                    const valeurActuelle = $(this).val();
                    $(this).find('option').each(function () {
                        $(this).prop('disabled', this.value !== '' && this.value !== valeurActuelle && selectionnes.includes(this.value));
                    });
                });
            }

            function ajouterLigneBc() {
                const html = $('#gabarit-ligne-bc').html().replaceAll('__index__', ligneIndex++);
                const $ligne = $(html);
                $('#lignes-bon-commande').append($ligne);
                $ligne.find('.select2-article-bc').select2({ dropdownParent: $('#modal-bon-commande'), width: '100%' });
                actualiserOptionsArticlesDisponiblesBc();
            }

            $('#btn-ajouter-ligne-bc').on('click', ajouterLigneBc);

            $('#lignes-bon-commande').on('change', '.select2-article-bc', function () {
                const prix = $(this).find(':selected').data('prix');
                if (prix !== undefined) {
                    $(this).closest('.ligne-bc').find('.ligne-bc-prix').val(prix);
                }
                actualiserOptionsArticlesDisponiblesBc();
                recalculerTotalBc();
            });

            $('#lignes-bon-commande').on('input', '.ligne-bc-quantite, .ligne-bc-prix', recalculerTotalBc);

            $('#lignes-bon-commande').on('click', '.btn-quantite-bc-plus', function () {
                const $input = $(this).closest('.input-group').find('.ligne-bc-quantite');
                $input.val((parseInt($input.val(), 10) || 0) + 1).trigger('input');
            });

            $('#lignes-bon-commande').on('click', '.btn-quantite-bc-moins', function () {
                const $input = $(this).closest('.input-group').find('.ligne-bc-quantite');
                $input.val(Math.max(1, (parseInt($input.val(), 10) || 0) - 1)).trigger('input');
            });

            $('#lignes-bon-commande').on('click', '.btn-supprimer-ligne-bc', function () {
                $(this).closest('.ligne-bc').remove();
                actualiserOptionsArticlesDisponiblesBc();
                recalculerTotalBc();
            });

            $('#btn-nouveau-bon-commande').on('click', function () {
                $('#form-bon-commande')[0].reset();
                $('#lignes-bon-commande').empty();
                ligneIndex = 0;
                $('.select2-fournisseur-bc').val('').trigger('change');
                ajouterLigneBc();
                recalculerTotalBc();
                modalBc.show();
            });

            $('#form-bon-commande').on('submit', function (e) {
                e.preventDefault();

                if ($('.ligne-bc').length === 0) {
                    Swal.fire({ icon: 'warning', text: 'Ajoutez au moins une ligne.' });
                    return;
                }

                $.post('/stock/bons-commande', $(this).serialize())
                    .done(function (res) {
                        modalBc.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-bons-commande').DataTable().ajax.reload();
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            function annulerBonCommande(id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Annuler ce bon de commande ?',
                    showCancelButton: true,
                    confirmButtonText: 'Annuler le bon',
                    cancelButtonText: 'Retour',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/stock/bons-commande/${id}/annuler`)
                        .done(function (res) {
                            modalDetail.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                            $('#table-bons-commande').DataTable().ajax.reload();
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            }

            function supprimerBonCommande(id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Supprimer ce bon de commande ?',
                    text: 'Cette action est définitive.',
                    showCancelButton: true,
                    confirmButtonText: 'Supprimer',
                    cancelButtonText: 'Retour',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/stock/bons-commande/${id}`, { _method: 'DELETE' })
                        .done(function (res) {
                            modalDetail.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                            $('#table-bons-commande').DataTable().ajax.reload();
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            }

            $('#detail-bc-annuler').on('click', function () { annulerBonCommande($(this).data('id')); });
            $('#detail-bc-supprimer').on('click', function () { supprimerBonCommande($(this).data('id')); });

            $('#table-bons-commande').on('click', '.btn-detail-bc', function () {
                const id = $(this).data('id');

                $.get(`/stock/bons-commande/${id}`, function (bc) {
                    const statutInfo = STATUTS[bc.statut] ?? { libelle: bc.statut, classe: 'bg-secondary' };
                    const recevable = bc.statut === 'en_attente' || bc.statut === 'partiellement_recu';
                    const supprimable = bc.statut === 'en_attente' || bc.statut === 'annule';

                    $('#detail-bc-reference').text(bc.reference ?? ('Bon de commande #' + bc.id));
                    $('#detail-bc-statut').html(`<span class="badge ${statutInfo.classe}">${statutInfo.libelle}</span>`);
                    $('#detail-bc-fournisseur').text(bc.fournisseur_nom);
                    $('#detail-bc-date').text(new Date(bc.date_commande).toLocaleDateString('fr-FR'));

                    const totalCommande = bc.lignes.reduce((s, l) => s + l.quantite_commandee, 0);
                    const totalRecu = bc.lignes.reduce((s, l) => s + l.quantite_recue, 0);
                    const pourcentage = totalCommande > 0 ? Math.round((totalRecu / totalCommande) * 100) : 0;
                    $('#detail-bc-taux').text(`${totalRecu} / ${totalCommande} articles`);
                    $('#detail-bc-progress').css('width', pourcentage + '%');

                    let totalEstime = 0;
                    const $lignes = $('#detail-bc-lignes').empty();
                    bc.lignes.forEach(function (ligne) {
                        totalEstime += parseFloat(ligne.montant_estime);
                        $lignes.append(`
                            <tr>
                                <td>${ligne.article_reference} — ${ligne.article_nom}</td>
                                <td class="text-end">${ligne.quantite_commandee}</td>
                                <td class="text-end">${ligne.quantite_recue}</td>
                                <td class="text-end">${formatMontant(ligne.prix_unitaire_estime)} FCFA</td>
                                <td class="text-end">${formatMontant(ligne.montant_estime)} FCFA</td>
                            </tr>
                        `);
                    });
                    $('#detail-bc-total').text(formatMontant(totalEstime) + ' FCFA');

                    if (bc.commentaire) {
                        $('#detail-bc-commentaire-bloc').removeClass('d-none');
                        $('#detail-bc-commentaire').text(bc.commentaire);
                    } else {
                        $('#detail-bc-commentaire-bloc').addClass('d-none');
                    }

                    $('#detail-bc-imprimer').attr('href', `/stock/bons-commande/${bc.id}/pdf`);
                    $('#detail-bc-pdf').attr('href', `/stock/bons-commande/${bc.id}/pdf?download=1`);
                    $('#detail-bc-excel').attr('href', `/stock/bons-commande/${bc.id}/excel`);
                    $('#detail-bc-recevoir').attr('href', `/stock/achats?bon_commande_id=${bc.id}`).toggleClass('d-none', !recevable);
                    $('#detail-bc-annuler').data('id', bc.id).toggleClass('d-none', bc.statut !== 'en_attente');
                    $('#detail-bc-supprimer').data('id', bc.id).toggleClass('d-none', !supprimable);

                    modalDetail.show();
                });
            });

            $('#table-bons-commande').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('stock.bons-commande.data') }}',
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_commande', name: 'date_commande' },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'fournisseur_nom', name: 'fournisseur_nom' },
                    { data: 'taux_reception', name: 'taux_reception', orderable: false, className: 'text-center' },
                    { data: 'statut_badge', name: 'statut', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (bc) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-bc" data-id="${bc.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[0, 'desc']],
            });
        });
        </script>
    @endpush
</x-app-layout>
