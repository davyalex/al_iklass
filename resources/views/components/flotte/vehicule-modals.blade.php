@props(['statuts', 'gestionnaires' => collect(), 'fenetreStatut' => null, 'modesPaiement' => collect()])

@php
    $badgesStatut = \App\Support\StatutVehiculeBadges::classes();
@endphp

{{-- Modale détail (fiche véhicule + historique) — réutilisée sur toutes les pages Flotte --}}
<div class="modal fade" id="modal-detail-vehicule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h5 class="modal-title mb-0 d-flex align-items-center gap-2">
                        <span id="detail-vehicule-code" class="fw-bold"></span>
                        <span class="badge" id="detail-vehicule-statut"></span>
                    </h5>
                    @canany(['flotte.vehicule.gerer', 'flotte.vehicule.statut.gerer'])
                        <select id="detail-select-statut" class="form-select form-select-sm w-auto">
                            @foreach ($statuts as $statut)
                                <option value="{{ $statut->id }}">{{ $statut->libelle }}</option>
                            @endforeach
                        </select>
                    @endcanany
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details" type="button">Détails</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="onglet-historique" data-bs-toggle="tab" data-bs-target="#tab-historique" type="button">Historique</button>
                    </li>
                </ul>
                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="tab-details">
                        <dl class="row mb-0 small">
                            <dt class="col-5">Libellé</dt><dd class="col-7" id="detail-libelle"></dd>
                            <dt class="col-5">Marque / Modèle</dt><dd class="col-7" id="detail-marque-modele"></dd>
                            <dt class="col-5">Immatriculation</dt><dd class="col-7" id="detail-immatriculation"></dd>
                            <dt class="col-5">Mise en circulation</dt><dd class="col-7" id="detail-date-circulation"></dd>
                            <dt class="col-5">Chauffeur</dt><dd class="col-7" id="detail-chauffeur"></dd>
                            <dt class="col-5">Recette journalière</dt><dd class="col-7" id="detail-recette"></dd>
                            <dt class="col-5">Gestionnaire</dt><dd class="col-7" id="detail-gestionnaire"></dd>
                        </dl>
                    </div>
                    <div class="tab-pane fade" id="tab-historique">
                        <div id="historique-liste" class="small">
                            <p class="text-muted">Chargement…</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                @canany(['flotte.vehicule.gerer', 'flotte.versement.gerer'])
                    <button type="button" class="btn btn-outline-primary me-auto d-none" id="btn-versement-depuis-detail">
                        <i class="bi bi-cash-coin me-1"></i>Faire un versement
                    </button>
                @endcanany
                @canany(['flotte.vehicule.gerer', 'flotte.vehicule.remise_circulation'])
                    <button type="button" class="btn btn-outline-success{{ auth()->user()->canAny(['flotte.vehicule.gerer', 'flotte.versement.gerer']) ? '' : ' me-auto' }}" id="btn-remise-circulation-depuis-detail">
                        <i class="bi bi-arrow-repeat me-1"></i>Remise en circulation
                    </button>
                @endcanany
                @can('flotte.vehicule.gerer')
                    <button type="button" class="btn btn-outline-danger" id="btn-archiver-depuis-detail">
                        <i class="bi bi-trash me-1"></i>Archiver
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-modifier-depuis-detail">
                        <i class="bi bi-pencil me-1"></i>Modifier
                    </button>
                @endcan
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@canany(['flotte.vehicule.gerer', 'flotte.versement.gerer'])
    {{-- Modale versement, pré-remplie depuis la fiche véhicule (gestionnaire et
    véhicule déjà connus par le contexte : pas de sélecteur) --}}
    <div class="modal fade" id="modal-versement-vehicule" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-versement-vehicule">
                    <input type="hidden" name="vehicule_id" id="versement-vehicule-id">
                    <input type="hidden" name="gestionnaire_id" id="versement-vehicule-gestionnaire-id">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau versement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <div class="small text-muted">Véhicule</div>
                                <div class="fw-semibold" id="versement-vehicule-code">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Gestionnaire</div>
                                <div class="fw-semibold" id="versement-vehicule-gestionnaire-nom">—</div>
                            </div>
                        </div>
                        <div class="form-text mb-2" id="versement-vehicule-reste-a-verser-info"></div>
                        <div class="mb-3">
                            <label class="form-label d-block">Type de versement</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type_versement_vehicule" id="versement-vehicule-type-total" checked>
                                <label class="btn btn-outline-primary" for="versement-vehicule-type-total">Versement total</label>
                                <input type="radio" class="btn-check" name="type_versement_vehicule" id="versement-vehicule-type-partiel">
                                <label class="btn btn-outline-primary" for="versement-vehicule-type-partiel">Versement partiel</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant (FCFA)</label>
                            <input type="number" name="montant" id="versement-vehicule-montant" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode de paiement</label>
                            <select name="mode_paiement_id" class="form-select" required>
                                <option value=""></option>
                                @foreach ($modesPaiement as $mode)
                                    <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_versement" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le versement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcanany

@canany(['flotte.vehicule.gerer', 'flotte.vehicule.remise_circulation'])
    {{-- Modale rapport de remise en circulation (chef mécanicien) --}}
    <div class="modal fade" id="modal-remise-circulation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-remise-circulation" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Remise en circulation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rapport</label>
                            <textarea name="rapport" class="form-control" rows="4" required maxlength="1000"></textarea>
                            <div class="invalid-feedback">Le rapport est obligatoire.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Remettre en circulation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcanany

@can('flotte.vehicule.gerer')
    {{-- Modale création / édition véhicule --}}
    <div class="modal fade" id="modal-vehicule" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-vehicule" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="vehicule-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-vehicule-titre">Nouveau véhicule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" required>
                                <div class="invalid-feedback">Le code est obligatoire.</div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Statut</label>
                                <select name="statut_id" class="form-select select2-statut" required>
                                    <option value=""></option>
                                    @foreach ($statuts as $statut)
                                        <option value="{{ $statut->id }}">{{ $statut->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le statut est obligatoire.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Libellé</label>
                            <input type="text" name="libelle" class="form-control" required>
                            <div class="invalid-feedback">Le libellé est obligatoire.</div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Marque</label>
                                <input type="text" name="marque" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Modèle</label>
                                <input type="text" name="modele" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Immatriculation</label>
                                <input type="text" name="immatriculation" class="form-control">
                                <div class="invalid-feedback">Cette immatriculation est déjà utilisée.</div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Mise en circulation</label>
                                <input type="date" name="date_mise_circulation" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Chauffeur</label>
                                <input type="text" name="chauffeur_nom" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Téléphone chauffeur</label>
                                <input type="text" name="chauffeur_telephone" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recette journalière (FCFA)</label>
                            <input type="number" name="recette_journaliere" class="form-control" value="0" min="0" step="0.01" required>
                            <div class="invalid-feedback">La recette doit être un nombre positif ou nul.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gestionnaire affecté</label>
                            <select name="gestionnaire_id" class="form-select select2-gestionnaire">
                                <option value="">Non affecté</option>
                                @foreach ($gestionnaires as $gestionnaire)
                                    <option value="{{ $gestionnaire->id }}">{{ $gestionnaire->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">L'utilisateur sélectionné n'a pas le rôle « gestionnaire ».</div>
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
@endcan

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.select2-statut, .select2-gestionnaire').select2({ dropdownParent: $('#modal-vehicule'), width: '100%' });

        const modalVehiculeEl = document.getElementById('modal-vehicule');
        const modalVehicule = modalVehiculeEl ? new bootstrap.Modal(modalVehiculeEl) : null;
        const modalDetail = new bootstrap.Modal('#modal-detail-vehicule');
        const $form = $('#form-vehicule');
        const badgesStatut = @json($badgesStatut);
        let vehiculeCourantId = null;

        // Verrouille le sélecteur de statut hors de la fenêtre horaire
        // autorisée (au lieu de laisser l'utilisateur choisir puis essuyer
        // une erreur serveur) : ne s'applique qu'à un gestionnaire soumis au
        // verrou, $fenetreStatut vaut null pour l'admin (jamais restreint).
        const fenetreStatut = @json($fenetreStatut);
        const fenetreDebut = fenetreStatut ? new Date(fenetreStatut.debut) : null;
        const fenetreFin = fenetreStatut ? new Date(fenetreStatut.fin) : null;

        function actualiserVerrouSelectStatut() {
            if (!fenetreStatut) {
                return;
            }

            const maintenant = new Date();
            const ouverte = maintenant >= fenetreDebut && maintenant <= fenetreFin;

            $('#detail-select-statut')
                .prop('disabled', !ouverte)
                .attr('title', ouverte ? '' : 'Modification possible uniquement pendant la fenêtre horaire autorisée.');
        }

        actualiserVerrouSelectStatut();
        if (fenetreStatut) {
            setInterval(actualiserVerrouSelectStatut, 30000);
        }

        function resetValidation() {
            $form.removeClass('was-validated');
            $form.find('.is-invalid').removeClass('is-invalid');
        }

        function formatMontant(valeur) {
            return new Intl.NumberFormat('fr-FR').format(valeur);
        }

        function ouvrirCreation() {
            if (!modalVehicule) {
                return;
            }
            $form[0].reset();
            resetValidation();
            $('#vehicule-id').val('');
            $('#modal-vehicule-titre').text('Nouveau véhicule');
            $('.select2-statut, .select2-gestionnaire').val('').trigger('change');
            modalVehicule.show();
        }

        function ouvrirEdition(id) {
            if (!modalVehicule) {
                return;
            }
            $.get(`/flotte/vehicules/${id}`, function (vehicule) {
                resetValidation();
                $('#vehicule-id').val(vehicule.id);
                $('#form-vehicule [name=code]').val(vehicule.code);
                $('#form-vehicule [name=libelle]').val(vehicule.libelle);
                $('#form-vehicule [name=marque]').val(vehicule.marque);
                $('#form-vehicule [name=modele]').val(vehicule.modele);
                $('#form-vehicule [name=immatriculation]').val(vehicule.immatriculation);
                $('#form-vehicule [name=date_mise_circulation]').val(vehicule.date_mise_circulation);
                $('#form-vehicule [name=chauffeur_nom]').val(vehicule.chauffeur_nom);
                $('#form-vehicule [name=chauffeur_telephone]').val(vehicule.chauffeur_telephone);
                $('#form-vehicule [name=recette_journaliere]').val(vehicule.recette_journaliere);
                $('.select2-statut').val(vehicule.statut_id).trigger('change');
                $('.select2-gestionnaire').val(vehicule.gestionnaire_id).trigger('change');
                $('#modal-vehicule-titre').text('Modifier le véhicule');
                modalVehicule.show();
            });
        }

        function archiverVehicule(id, code) {
            Swal.fire({
                icon: 'warning',
                text: `Archiver le véhicule « ${code} » ?`,
                showCancelButton: true,
                confirmButtonText: 'Archiver',
                cancelButtonText: 'Annuler',
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({ url: `/flotte/vehicules/${id}`, method: 'DELETE' })
                    .done(function (res) {
                        Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                    });
            });
        }

        // Bouton "Nouveau véhicule" : présent uniquement sur la page Véhicules,
        // absent ailleurs (jQuery ne fait rien sur une sélection vide).
        $('#btn-nouveau-vehicule').on('click', ouvrirCreation);

        // Délégué sur le document : les puces véhicule (.btn-voir-vehicule)
        // existent sur plusieurs pages (Véhicules, Gestionnaires).
        $(document).on('click', '.btn-voir-vehicule', function () {
            vehiculeCourantId = $(this).data('id');

            new bootstrap.Tab(document.querySelector('[data-bs-target="#tab-details"]')).show();
            $('#historique-liste').html('<p class="text-muted">Chargement…</p>');

            $.get(`/flotte/vehicules/${vehiculeCourantId}`, function (vehicule) {
                $('#detail-vehicule-code').text(vehicule.code);
                $('#detail-vehicule-statut')
                    .attr('class', 'badge ' + (badgesStatut[vehicule.statut?.code] || 'bg-secondary'))
                    .text(vehicule.statut?.libelle || 'Sans statut');
                if ($('#detail-select-statut').length) {
                    $('#detail-select-statut').val(vehicule.statut_id);
                    actualiserVerrouSelectStatut();
                }
                $('#detail-libelle').text(vehicule.libelle || '—');
                $('#detail-marque-modele').text([vehicule.marque, vehicule.modele].filter(Boolean).join(' ') || '—');
                $('#detail-immatriculation').text(vehicule.immatriculation || '—');
                $('#detail-date-circulation').text(vehicule.date_mise_circulation || '—');
                $('#detail-chauffeur').text([vehicule.chauffeur_nom, vehicule.chauffeur_telephone].filter(Boolean).join(' — ') || '—');
                $('#detail-recette').text(formatMontant(vehicule.recette_journaliere) + ' FCFA/jour');
                $('#detail-gestionnaire').text(vehicule.gestionnaire?.name || 'Non affecté');

                $('#btn-archiver-depuis-detail').data('code', vehicule.code);

                // Le versement n'a de sens que si le véhicule a un gestionnaire :
                // on garde son id/nom sous la main pour la modale de versement,
                // et on masque le bouton sinon.
                $('#btn-versement-depuis-detail')
                    .data('vehicule-code', vehicule.code)
                    .data('gestionnaire-id', vehicule.gestionnaire_id)
                    .data('gestionnaire-nom', vehicule.gestionnaire?.name)
                    .toggleClass('d-none', !vehicule.gestionnaire_id);

                modalDetail.show();
            });
        });

        // Versement depuis la fiche véhicule : véhicule et gestionnaire déjà
        // connus par le contexte (pas de sélecteur), même bascule
        // total/partiel que sur la page Versements.
        const modalVersementVehiculeEl = document.getElementById('modal-versement-vehicule');
        const modalVersementVehicule = modalVersementVehiculeEl ? new bootstrap.Modal(modalVersementVehiculeEl) : null;

        function appliquerTypeVersementVehicule() {
            const reste = $('#form-versement-vehicule').data('reste-a-verser') || 0;

            if ($('#versement-vehicule-type-total').is(':checked')) {
                $('#versement-vehicule-montant').val(reste > 0 ? reste : '').prop('readonly', true);
            } else {
                $('#versement-vehicule-montant').val('').prop('readonly', false).trigger('focus');
            }
        }

        $('#modal-versement-vehicule input[name=type_versement_vehicule]').on('change', appliquerTypeVersementVehicule);

        $('#btn-versement-depuis-detail').on('click', function () {
            const gestionnaireId = $(this).data('gestionnaire-id');

            $('#form-versement-vehicule')[0].reset();
            $('#versement-vehicule-id').val(vehiculeCourantId);
            $('#versement-vehicule-gestionnaire-id').val(gestionnaireId);
            $('#versement-vehicule-code').text($(this).data('vehicule-code'));
            $('#versement-vehicule-gestionnaire-nom').text($(this).data('gestionnaire-nom'));
            $('#versement-vehicule-reste-a-verser-info').text('Chargement…');
            $('#form-versement-vehicule').removeData('reste-a-verser');

            modalDetail.hide();

            $.get('{{ route('flotte.versements.kpis') }}', { gestionnaire_id: gestionnaireId }, function (kpis) {
                const reste = parseFloat(kpis.reste_a_verser_jour.replace(/\s/g, '').replace(',', '.')) || 0;
                $('#form-versement-vehicule').data('reste-a-verser', reste);
                $('#versement-vehicule-reste-a-verser-info').text('Reste à verser (jour) : ' + kpis.reste_a_verser_jour + ' FCFA');
                appliquerTypeVersementVehicule();
            });

            modalVersementVehicule.show();
        });

        $('#form-versement-vehicule').on('submit', function (e) {
            e.preventDefault();

            $.post('{{ route('flotte.versements.store') }}', $(this).serialize())
                .done(function (res) {
                    modalVersementVehicule.hide();
                    Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                })
                .fail(function (xhr) {
                    const erreurs = xhr.responseJSON?.errors;
                    const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                    Swal.fire({ icon: 'error', text: msg });
                });
        });

        $('[data-bs-target="#tab-historique"]').on('shown.bs.tab', function () {
            if (!vehiculeCourantId) {
                return;
            }

            $.get(`/flotte/vehicules/${vehiculeCourantId}/historique`, function (res) {
                if (!res.evenements.length) {
                    $('#historique-liste').html('<p class="text-muted mb-0">Aucun historique pour ce véhicule.</p>');
                    return;
                }

                const lignes = res.evenements.map(function (evt) {
                    const icone = evt.type === 'mouvement_stock' ? 'bi-box-arrow-up-right' : 'bi-arrow-repeat';
                    const date = evt.date ? new Date(evt.date).toLocaleString('fr-FR') : '';
                    return `<div class="d-flex justify-content-between align-items-start border-bottom py-2">
                        <div><i class="bi ${icone} me-2 text-muted"></i>${evt.libelle}${evt.detail ? ' <span class="text-muted">('+evt.detail+')</span>' : ''}</div>
                        <div class="text-muted text-nowrap ms-2">${date}</div>
                    </div>`;
                }).join('');

                $('#historique-liste').html(lignes);
            });
        });

        $('#modal-detail-vehicule').on('change', '#detail-select-statut', function () {
            const nouveauStatutId = $(this).val();

            $.ajax({ url: `/flotte/vehicules/${vehiculeCourantId}/statut`, method: 'PATCH', data: { statut_id: nouveauStatutId } })
                .done(function (res) {
                    Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                })
                .fail(function (xhr) {
                    Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                });
        });

        const modalRemiseCirculationEl = document.getElementById('modal-remise-circulation');
        const modalRemiseCirculation = modalRemiseCirculationEl ? new bootstrap.Modal(modalRemiseCirculationEl) : null;
        const $formRemiseCirculation = $('#form-remise-circulation');

        $('#btn-remise-circulation-depuis-detail').on('click', function () {
            modalDetail.hide();
            $formRemiseCirculation[0].reset();
            $formRemiseCirculation.removeClass('was-validated');
            modalRemiseCirculation.show();
        });

        $formRemiseCirculation.on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const formEl = $formRemiseCirculation[0];
            if (!formEl.checkValidity()) {
                $formRemiseCirculation.addClass('was-validated');
                return;
            }

            $.post(`/flotte/vehicules/${vehiculeCourantId}/remise-circulation`, $formRemiseCirculation.serialize())
                .done(function (res) {
                    modalRemiseCirculation.hide();
                    Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                        .then(() => window.location.reload());
                })
                .fail(function (xhr) {
                    const msg = xhr.responseJSON?.errors?.rapport?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                    Swal.fire({ icon: 'error', text: msg });
                });
        });

        $('#btn-modifier-depuis-detail').on('click', function () {
            modalDetail.hide();
            ouvrirEdition(vehiculeCourantId);
        });

        $('#btn-archiver-depuis-detail').on('click', function () {
            modalDetail.hide();
            archiverVehicule(vehiculeCourantId, $(this).data('code'));
        });

        if ($form.length) {
            $form.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const formEl = $form[0];
                $form.find('.is-invalid').removeClass('is-invalid');

                if (!formEl.checkValidity()) {
                    $form.addClass('was-validated');
                    return;
                }

                const id = $('#vehicule-id').val();
                const url = id ? `/flotte/vehicules/${id}` : '/flotte/vehicules';
                const data = $form.serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        resetValidation();
                        modalVehicule.hide();
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
        }
    });
    </script>
@endpush
