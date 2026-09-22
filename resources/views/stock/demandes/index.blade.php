<x-app-layout>
    <x-slot name="header">Demandes de pièces</x-slot>

    <div class="row g-3 mb-3 row-cols-1 row-cols-sm-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">En attente</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-en-attente">{{ $kpis['en_attente'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Validées (mois)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-validees-mois">{{ $kpis['validees_mois'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Rejetées (mois)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-rejetees-mois">{{ $kpis['rejetees_mois'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        @can('stock.demande.creer')
            <button type="button" class="btn btn-primary" id="btn-nouvelle-demande">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle demande
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-demandes" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Véhicule</label>
                    <select name="vehicule_id" class="form-select form-select-sm select2-filtre-vehicule">
                        <option value="">Tous</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->id }}">{{ $vehicule->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="en_attente">En attente</option>
                        <option value="validee">Validée</option>
                        <option value="rejetee">Rejetée</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-demandes">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-demandes">
                        Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-demandes">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Véhicule</th>
                        <th class="text-end">Articles</th>
                        <th class="text-end">Quantité</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 60px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @can('stock.demande.creer')
        {{-- Modale nouvelle demande --}}
        <div class="modal fade" id="modal-demande" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="form-demande">
                        <div class="modal-header">
                            <h5 class="modal-title">Nouvelle demande de pièces</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Véhicule</label>
                                    <select name="vehicule_id" class="form-select select2-vehicule-demande" required>
                                        <option value=""></option>
                                        @foreach ($vehicules as $vehicule)
                                            <option value="{{ $vehicule->id }}">{{ $vehicule->code }} — {{ $vehicule->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date_demande" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Motif <span class="text-muted">(optionnel)</span></label>
                                <input type="text" name="motif" class="form-control" placeholder="Ex : Changement plaquettes de frein">
                            </div>

                            <hr>

                            <div id="lignes-demande"></div>

                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-ajouter-ligne-demande">
                                <i class="bi bi-plus-lg me-1"></i>Ajouter un article
                            </button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Gabarit d'une ligne de demande --}}
        <template id="gabarit-ligne-demande">
            <div class="row align-items-end ligne-demande mb-2">
                <div class="col-9 col-md-9">
                    <label class="form-label small">Article</label>
                    <select name="lignes[__index__][article_id]" class="form-select select2-article-demande" required>
                        <option value=""></option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}">{{ $article->reference }} — {{ $article->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-2 col-md-2">
                    <label class="form-label small">Quantité</label>
                    <input type="number" name="lignes[__index__][quantite]" class="form-control ligne-quantite-demande" min="1" value="1" required>
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-outline-danger btn-supprimer-ligne-demande"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </template>
    @endcan

    {{-- Modale détail --}}
    <div class="modal fade" id="modal-detail-demande" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="detail-demande-reference">—</h5>
                        <span id="detail-demande-statut"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="small text-muted">Véhicule</div>
                            <div class="fw-semibold" id="detail-demande-vehicule">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Demandeur</div>
                            <div class="fw-semibold" id="detail-demande-demandeur">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Date</div>
                            <div class="fw-semibold" id="detail-demande-date">—</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">Motif</div>
                            <div class="fw-semibold" id="detail-demande-motif">—</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th class="text-end">Quantité demandée</th>
                                </tr>
                            </thead>
                            <tbody id="detail-demande-lignes"></tbody>
                        </table>
                    </div>

                    <div id="detail-demande-traitement" class="d-none">
                        <hr>
                        <div class="small text-muted">Traitée par</div>
                        <div class="fw-semibold" id="detail-demande-traite-par">—</div>
                        <div id="detail-demande-commentaire-bloc" class="d-none mt-2">
                            <div class="small text-muted">Motif</div>
                            <div class="fst-italic" id="detail-demande-commentaire-traitement">—</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @if ($peutTraiter)
                        <button type="button" class="btn btn-outline-danger me-auto" id="btn-rejeter-demande">
                            <i class="bi bi-x-lg me-1"></i>Rejeter
                        </button>
                        <button type="button" class="btn btn-success" id="btn-valider-demande">
                            <i class="bi bi-check-lg me-1"></i>Valider (créer la sortie)
                        </button>
                    @endif
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const peutTraiter = @json($peutTraiter);
            let demandeCouranteId = null;
            const modalDetail = new bootstrap.Modal('#modal-detail-demande');

            $('.select2-filtre-vehicule').select2({ width: '100%', placeholder: 'Tous' });

            const statutsBadges = {
                en_attente: '<span class="badge bg-secondary">En attente</span>',
                validee: '<span class="badge bg-success">Validée</span>',
                rejetee: '<span class="badge bg-danger">Rejetée</span>',
            };

            function rafraichirKpis() {
                $.get('{{ route('stock.demandes.kpis') }}', function (kpis) {
                    $('#kpi-en-attente').text(kpis.en_attente);
                    $('#kpi-validees-mois').text(kpis.validees_mois);
                    $('#kpi-rejetees-mois').text(kpis.rejetees_mois);
                });
            }

            @can('stock.demande.creer')
                let ligneIndex = 0;
                const modalDemande = new bootstrap.Modal('#modal-demande');

                function ajouterLigneDemande() {
                    const html = $('#gabarit-ligne-demande').html().replaceAll('__index__', ligneIndex++);
                    const $ligne = $(html);
                    $('#lignes-demande').append($ligne);
                    $ligne.find('.select2-article-demande').select2({ dropdownParent: $('#modal-demande'), width: '100%' });
                    actualiserOptionsArticlesDemande();
                }

                function actualiserOptionsArticlesDemande() {
                    const selectionnes = $('.select2-article-demande').map(function () { return $(this).val(); }).get().filter(Boolean);

                    $('.select2-article-demande').each(function () {
                        const valeurActuelle = $(this).val();
                        $(this).find('option').each(function () {
                            $(this).prop('disabled', this.value !== '' && this.value !== valeurActuelle && selectionnes.includes(this.value));
                        });
                    });
                }

                $('.select2-vehicule-demande').select2({ dropdownParent: $('#modal-demande'), width: '100%' });

                $('#btn-nouvelle-demande').on('click', function () {
                    $('#form-demande')[0].reset();
                    $('.select2-vehicule-demande').val('').trigger('change');
                    $('#lignes-demande').empty();
                    ligneIndex = 0;
                    ajouterLigneDemande();
                    modalDemande.show();
                });

                $('#btn-ajouter-ligne-demande').on('click', ajouterLigneDemande);

                $('#lignes-demande').on('change', '.select2-article-demande', actualiserOptionsArticlesDemande);

                $('#lignes-demande').on('click', '.btn-supprimer-ligne-demande', function () {
                    $(this).closest('.ligne-demande').remove();
                    actualiserOptionsArticlesDemande();
                });

                $('#form-demande').on('submit', function (e) {
                    e.preventDefault();

                    if ($('.ligne-demande').length === 0) {
                        Swal.fire({ icon: 'warning', text: 'Ajoutez au moins un article.' });
                        return;
                    }

                    $.post('{{ route('stock.demandes.store') }}', $(this).serialize())
                        .done(function (res) {
                            modalDemande.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                            $('#table-demandes').DataTable().ajax.reload();
                            rafraichirKpis();
                        })
                        .fail(function (xhr) {
                            const erreurs = xhr.responseJSON?.errors;
                            const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                            Swal.fire({ icon: 'error', text: msg });
                        });
                });
            @endcan

            function filtresDemandes() {
                return {
                    date_debut: $('#filtres-demandes [name=date_debut]').val(),
                    date_fin: $('#filtres-demandes [name=date_fin]').val(),
                    vehicule_id: $('#filtres-demandes [name=vehicule_id]').val(),
                    statut: $('#filtres-demandes [name=statut]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtresDemandes()).some((v) => !!v);
                $('#btn-reset-demandes').toggleClass('d-none', !actif);
            }

            $('#filtres-demandes').on('change input', actualiserBoutonReset);

            const tableDemandes = $('#table-demandes').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.demandes.data') }}',
                    data: (d) => Object.assign(d, filtresDemandes()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_demande', name: 'date_demande' },
                    { data: 'reference', name: 'reference' },
                    { data: 'vehicule_code', name: 'vehicule_code' },
                    { data: 'lignes_count', name: 'lignes_count', className: 'text-end', orderable: false },
                    { data: 'quantite_totale', name: 'quantite_totale', className: 'text-end', orderable: false },
                    { data: 'statut_badge', name: 'statut', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (d) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-demande" data-id="${d.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-demandes').on('click', () => tableDemandes.ajax.reload());

            $('#btn-reset-demandes').on('click', function () {
                $('#filtres-demandes')[0].reset();
                $('.select2-filtre-vehicule').val('').trigger('change');
                tableDemandes.ajax.reload();
            });

            $('#table-demandes').on('click', '.btn-detail-demande', function () {
                demandeCouranteId = $(this).data('id');

                $.get(`/stock/demandes/${demandeCouranteId}`, function (demande) {
                    $('#detail-demande-reference').text(demande.reference);
                    $('#detail-demande-statut').html(statutsBadges[demande.statut] || demande.statut);
                    $('#detail-demande-vehicule').text(demande.vehicule_code);
                    $('#detail-demande-demandeur').text(demande.demandeur_nom);
                    $('#detail-demande-date').text(new Date(demande.date_demande).toLocaleDateString('fr-FR'));
                    $('#detail-demande-motif').text(demande.motif || '—');

                    const $lignes = $('#detail-demande-lignes').empty();
                    demande.lignes.forEach(function (ligne) {
                        $lignes.append(`<tr><td>${ligne.article_reference} — ${ligne.article_nom}</td><td class="text-end">${ligne.quantite}</td></tr>`);
                    });

                    if (demande.statut === 'en_attente') {
                        $('#detail-demande-traitement').addClass('d-none');
                        if (peutTraiter) {
                            $('#btn-valider-demande, #btn-rejeter-demande').removeClass('d-none');
                        }
                    } else {
                        $('#detail-demande-traitement').removeClass('d-none');
                        $('#detail-demande-traite-par').text(demande.traite_par_nom || '—');
                        if (demande.statut === 'rejetee' && demande.commentaire_traitement) {
                            $('#detail-demande-commentaire-bloc').removeClass('d-none');
                            $('#detail-demande-commentaire-traitement').text(demande.commentaire_traitement);
                        } else {
                            $('#detail-demande-commentaire-bloc').addClass('d-none');
                        }
                        if (peutTraiter) {
                            $('#btn-valider-demande, #btn-rejeter-demande').addClass('d-none');
                        }
                    }

                    modalDetail.show();
                });
            });

            @if ($peutTraiter)
                $('#btn-valider-demande').on('click', function () {
                    Swal.fire({
                        icon: 'question',
                        text: 'Valider cette demande et créer la sortie de stock correspondante ?',
                        showCancelButton: true,
                        confirmButtonText: 'Valider',
                        cancelButtonText: 'Annuler',
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.post(`/stock/demandes/${demandeCouranteId}/valider`)
                            .done(function (res) {
                                modalDetail.hide();
                                Swal.fire({ icon: 'success', text: res.message, timer: 2200, showConfirmButton: false });
                                tableDemandes.ajax.reload();
                                rafraichirKpis();
                            })
                            .fail(function (xhr) {
                                Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                            });
                    });
                });

                $('#btn-rejeter-demande').on('click', function () {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Rejeter la demande',
                        input: 'text',
                        inputLabel: 'Motif du rejet',
                        inputPlaceholder: 'Ex : article indisponible',
                        showCancelButton: true,
                        confirmButtonText: 'Rejeter',
                        cancelButtonText: 'Annuler',
                        inputValidator: (value) => !value ? 'Le motif est obligatoire.' : undefined,
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.post(`/stock/demandes/${demandeCouranteId}/rejeter`, { motif: result.value })
                            .done(function (res) {
                                modalDetail.hide();
                                Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                                tableDemandes.ajax.reload();
                                rafraichirKpis();
                            })
                            .fail(function (xhr) {
                                Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                            });
                    });
                });
            @endif
        });
        </script>
    @endpush
</x-app-layout>
