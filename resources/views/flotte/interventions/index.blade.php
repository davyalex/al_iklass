<x-app-layout>
    <x-slot name="header">Interventions</x-slot>

    <div class="al-page-actions">
        @can('interventions.type.gerer')
            <button type="button" class="btn btn-outline-secondary" id="btn-gerer-types-panne">
                <i class="bi bi-tags me-1"></i>Types de panne
            </button>
        @endcan
        @can('interventions.declarer')
            <button type="button" class="btn btn-primary" id="btn-declarer-panne">
                <i class="bi bi-exclamation-triangle me-1"></i>Déclarer une panne
            </button>
        @endcan
    </div>

    {{-- Filtres --}}
    <div class="card al-filtres mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Véhicule</label>
                    <select id="filtre-intervention-vehicule" class="form-select form-select-sm select2-filtre-intervention-vehicule">
                        <option value="">Tous les véhicules</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->id }}">{{ $vehicule->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select id="filtre-intervention-type" class="form-select form-select-sm">
                        <option value="">Tous les types</option>
                        @foreach ($typesPanne as $type)
                            <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Période du</label>
                    <input type="date" id="filtre-intervention-du" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">au</label>
                    <input type="date" id="filtre-intervention-au" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md al-filtres-actions">
                    <x-filtre-reset id="btn-reset-filtre-intervention" />
                </div>
            </div>
        </div>
    </div>

    @if ($interventionsEnCours->isEmpty())
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                Aucune intervention en cours.
            </div>
        </div>
    @else
        <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3" id="grille-interventions">
            @foreach ($interventionsEnCours as $intervention)
                <div class="col carte-intervention" data-vehicule-id="{{ $intervention->vehicule_id }}" data-type-panne-id="{{ $intervention->type_panne_id }}" data-date-debut="{{ $intervention->date_debut->toDateString() }}">
                    <div class="card shadow-sm border-0 bg-white h-100 border-start border-4 border-warning">
                        <div class="card-body d-flex flex-column h-100">
                            <div class="fw-semibold">{{ $intervention->vehicule_code }}</div>
                            @if ($intervention->type_panne_libelle)
                                <span class="badge bg-warning text-dark align-self-start my-1">{{ $intervention->type_panne_libelle }}</span>
                            @endif
                            <p class="small text-muted mb-2 text-truncate">{{ $intervention->description }}</p>
                            <div class="small text-muted mb-3">Depuis le {{ $intervention->date_debut->format('d/m/Y') }}</div>

                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-fill btn-detail-intervention" data-id="{{ $intervention->vehicule_id }}" data-code="{{ $intervention->vehicule_code }}">
                                    <i class="bi bi-eye me-1"></i>Détail
                                </button>
                                @can('interventions.declarer')
                                    <button type="button" class="btn btn-sm btn-outline-secondary flex-fill btn-modifier-intervention"
                                            data-id="{{ $intervention->id }}" data-code="{{ $intervention->vehicule_code }}"
                                            data-type-panne-id="{{ $intervention->type_panne_id }}" data-description="{{ $intervention->description }}"
                                            data-statut-id="{{ $intervention->vehicule->statut_id }}">
                                        <i class="bi bi-pencil me-1"></i>Modifier
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success flex-fill btn-cloturer-intervention" data-id="{{ $intervention->id }}" data-code="{{ $intervention->vehicule_code }}">
                                        <i class="bi bi-check2-circle me-1"></i>Clôturer
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modale détail : intervention en cours + historique du véhicule --}}
    <div class="modal fade" id="modal-detail-intervention" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détail — <span id="detail-intervention-code"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="small text-uppercase text-muted">Intervention en cours</h6>
                    <div id="detail-intervention-active" class="small mb-3"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="small text-uppercase text-muted mb-0">Historique</h6>
                        <a href="#" id="lien-historique-intervention-vehicule" class="small">Voir tout l'historique</a>
                    </div>
                    <div id="detail-intervention-historique" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @can('interventions.declarer')
        {{-- Modale déclaration d'une panne --}}
        <div class="modal fade" id="modal-declarer-panne" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-declarer-panne" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Déclarer une panne</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Véhicule</label>
                                <select name="vehicule_id" class="form-select select2-vehicule-dp" required>
                                    <option value=""></option>
                                    @foreach ($vehicules as $vehicule)
                                        <option value="{{ $vehicule->id }}">{{ $vehicule->code }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le véhicule est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type de panne <span class="text-muted">(optionnel)</span></label>
                                <select name="type_panne_id" class="form-select">
                                    <option value=""></option>
                                    @foreach ($typesPanne as $type)
                                        <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" maxlength="1000" required></textarea>
                                <div class="invalid-feedback">La description est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Statut du véhicule</label>
                                <select name="statut_id" class="form-select" required>
                                    <option value=""></option>
                                    @foreach ($statutsPanne as $statut)
                                        <option value="{{ $statut->id }}">{{ $statut->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le statut est obligatoire.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Déclarer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale modification d'une intervention en cours --}}
        <div class="modal fade" id="modal-modifier-intervention" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-modifier-intervention" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier — <span id="modifier-intervention-code"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type de panne <span class="text-muted">(optionnel)</span></label>
                                <select name="type_panne_id" class="form-select">
                                    <option value=""></option>
                                    @foreach ($typesPanne as $type)
                                        <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" maxlength="1000" required></textarea>
                                <div class="invalid-feedback">La description est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Statut du véhicule</label>
                                <select name="statut_id" class="form-select" required>
                                    <option value=""></option>
                                    @foreach ($statutsPanne as $statut)
                                        <option value="{{ $statut->id }}">{{ $statut->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le statut est obligatoire.</div>
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

        {{-- Modale clôture : rapport + date, remet le véhicule en circulation --}}
        <div class="modal fade" id="modal-cloturer-intervention" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-cloturer-intervention" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Clôturer — <span id="cloturer-intervention-code"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_fin" class="form-control" required>
                                <div class="invalid-feedback">La date est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rapport</label>
                                <textarea name="rapport" class="form-control" rows="3" maxlength="1000" required></textarea>
                                <div class="invalid-feedback">Le rapport est obligatoire.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">Clôturer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('interventions.type.gerer')
        {{-- Modale gestion des types de panne (référentiel) --}}
        <div class="modal fade" id="modal-types-panne" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Types de panne</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Libellé</th>
                                        <th>Statut</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tousLesTypesPanne as $type)
                                        <tr class="{{ $type->actif ? '' : 'text-muted' }}">
                                            <td>{{ $type->libelle }}</td>
                                            <td>
                                                @if ($type->actif)
                                                    <span class="badge bg-success">Actif</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactif</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-type-panne"
                                                        data-id="{{ $type->id }}" data-libelle="{{ $type->libelle }}"
                                                        data-actif="{{ $type->actif ? 1 : 0 }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="btn-nouveau-type-panne">
                            <i class="bi bi-plus-lg me-1"></i>Nouveau type
                        </button>

                        <form id="form-type-panne" class="needs-validation border-top pt-3 d-none" novalidate>
                            <input type="hidden" name="id" id="type-panne-id">
                            <h6 class="small text-uppercase text-muted" id="form-type-panne-titre">Nouveau type</h6>
                            <div class="mb-2">
                                <label class="form-label small">Libellé</label>
                                <input type="text" name="libelle" class="form-control form-control-sm" required>
                                <div class="invalid-feedback">Le libellé est obligatoire.</div>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="actif" id="type-panne-actif" class="form-check-input" value="1" checked>
                                <label class="form-check-label small" for="type-panne-actif">Actif</label>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-annuler-type-panne">Annuler</button>
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
            $('.select2-filtre-intervention-vehicule').select2({ width: '100%', selectionCssClass: 'select2-sm' });

            // --- Filtres ---
            function filtresInterventions() {
                return {
                    vehicule_id: $('#filtre-intervention-vehicule').val() || '',
                    type_panne_id: $('#filtre-intervention-type').val() || '',
                    date_debut: $('#filtre-intervention-du').val(),
                    date_fin: $('#filtre-intervention-au').val(),
                };
            }

            function appliquerFiltresInterventions() {
                const f = filtresInterventions();
                const filtreActif = !!f.vehicule_id || !!f.type_panne_id || !!f.date_debut || !!f.date_fin;

                $('.carte-intervention').each(function () {
                    const $carte = $(this);
                    const correspondVehicule = !f.vehicule_id || String($carte.data('vehicule-id')) === f.vehicule_id;
                    const correspondType = !f.type_panne_id || String($carte.data('type-panne-id')) === f.type_panne_id;
                    const dateDebut = String($carte.data('date-debut'));
                    const correspondDate = (!f.date_debut || dateDebut >= f.date_debut) && (!f.date_fin || dateDebut <= f.date_fin);
                    $carte.toggleClass('d-none', !(correspondVehicule && correspondType && correspondDate));
                });

                $('#btn-reset-filtre-intervention').toggleClass('d-none', !filtreActif);
            }

            $('#filtre-intervention-vehicule, #filtre-intervention-type, #filtre-intervention-du, #filtre-intervention-au').on('change', appliquerFiltresInterventions);

            $('#btn-reset-filtre-intervention').on('click', function () {
                $('#filtre-intervention-type').val('');
                $('#filtre-intervention-du').val('');
                $('#filtre-intervention-au').val('');
                $('.select2-filtre-intervention-vehicule').val('').trigger('change');
                appliquerFiltresInterventions();
            });

            // --- Détail ---
            const modalDetailEl = document.getElementById('modal-detail-intervention');
            const modalDetail = modalDetailEl ? new bootstrap.Modal(modalDetailEl) : null;

            function chargerDetail(vehiculeId, vehiculeCode) {
                $('#detail-intervention-code').text(vehiculeCode);
                $('#detail-intervention-active').html('<p class="text-muted mb-0">Chargement…</p>');
                $('#detail-intervention-historique').html('');
                $('#lien-historique-intervention-vehicule').attr('href', '{{ route('flotte.interventions.historique.index') }}?vehicule_id=' + vehiculeId);

                $.get(`/flotte/interventions/vehicules/${vehiculeId}/detail`, function (res) {
                    if (!res.active) {
                        $('#detail-intervention-active').html('<p class="text-muted mb-0">Aucune intervention en cours pour ce véhicule.</p>');
                    } else {
                        const a = res.active;
                        let boutonsActions = '';
                        if (@json(auth()->user()->can('interventions.declarer'))) {
                            const descriptionAttr = (a.description || '').replace(/"/g, '&quot;');
                            boutonsActions = `<div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-intervention" data-id="${a.id}" data-code="${vehiculeCode}" data-type-panne-id="${a.type_panne_id ?? ''}" data-description="${descriptionAttr}" data-statut-id="${a.statut_vehicule_id}">Modifier</button>
                                <button type="button" class="btn btn-sm btn-success btn-cloturer-intervention" data-id="${a.id}" data-code="${vehiculeCode}">Clôturer</button>
                            </div>`;
                        }
                        $('#detail-intervention-active').html(`<div class="border-bottom py-2">
                            ${a.type_panne_libelle ? '<span class="badge bg-warning text-dark">' + a.type_panne_libelle + '</span> ' : ''}
                            <span>Depuis le <strong>${a.date_debut}</strong></span>
                            <br><span class="text-muted">${a.description}</span>
                            ${boutonsActions}
                        </div>`);
                    }

                    if (!res.historique.length) {
                        $('#detail-intervention-historique').html('<p class="text-muted mb-0">Aucun historique pour ce véhicule.</p>');
                    } else {
                        $('#detail-intervention-historique').html(res.historique.map(function (i) {
                            return `<div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                <div>
                                    ${i.type_panne_libelle ? '<span class="badge bg-light text-dark border">' + i.type_panne_libelle + '</span> ' : ''}
                                    <span class="text-muted">${i.date_debut} → ${i.date_fin}</span>
                                    <br><span class="text-muted">${i.description}</span>
                                    ${i.rapport ? '<br><span class="text-muted">Rapport : ' + i.rapport + '</span>' : ''}
                                </div>
                            </div>`;
                        }).join(''));
                    }

                    modalDetail.show();
                });
            }

            $(document).on('click', '.btn-detail-intervention', function () {
                chargerDetail($(this).data('id'), $(this).data('code'));
            });

            @can('interventions.declarer')
                // --- Déclarer une panne ---
                $('.select2-vehicule-dp').select2({ width: '100%', dropdownParent: $('#modal-declarer-panne') });

                const modalDeclarerEl = document.getElementById('modal-declarer-panne');
                const modalDeclarer = modalDeclarerEl ? new bootstrap.Modal(modalDeclarerEl) : null;
                const $formDeclarer = $('#form-declarer-panne');

                $('#btn-declarer-panne').on('click', function () {
                    $formDeclarer[0].reset();
                    $formDeclarer.removeClass('was-validated');
                    $('.select2-vehicule-dp').val('').trigger('change');
                    modalDeclarer.show();
                });

                $formDeclarer.on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!$formDeclarer[0].checkValidity()) {
                        $formDeclarer.addClass('was-validated');
                        return;
                    }

                    $.post('{{ route('flotte.interventions.store') }}', $formDeclarer.serialize())
                        .done(function (res) {
                            modalDeclarer.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            const erreurs = xhr.responseJSON?.errors;
                            const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                            Swal.fire({ icon: 'error', text: msg });
                        });
                });

                // --- Modifier une intervention en cours ---
                const modalModifierEl = document.getElementById('modal-modifier-intervention');
                const modalModifier = modalModifierEl ? new bootstrap.Modal(modalModifierEl) : null;
                const $formModifier = $('#form-modifier-intervention');
                let modifierInterventionId = null;

                $(document).on('click', '.btn-modifier-intervention', function () {
                    modifierInterventionId = $(this).data('id');

                    $formModifier[0].reset();
                    $formModifier.removeClass('was-validated');
                    $('#modifier-intervention-code').text($(this).data('code'));
                    $formModifier.find('[name=type_panne_id]').val($(this).data('type-panne-id') || '');
                    $formModifier.find('[name=description]').val($(this).data('description'));
                    $formModifier.find('[name=statut_id]').val($(this).data('statut-id'));

                    modalDetail?.hide();
                    modalModifier.show();
                });

                $formModifier.on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!$formModifier[0].checkValidity()) {
                        $formModifier.addClass('was-validated');
                        return;
                    }

                    $.post(`/flotte/interventions/${modifierInterventionId}`, $formModifier.serialize() + '&_method=PUT')
                        .done(function (res) {
                            modalModifier.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            const erreurs = xhr.responseJSON?.errors;
                            const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                            Swal.fire({ icon: 'error', text: msg });
                        });
                });

                // --- Clôturer une intervention (rapport + date, remet en circulation) ---
                const modalCloturerEl = document.getElementById('modal-cloturer-intervention');
                const modalCloturer = modalCloturerEl ? new bootstrap.Modal(modalCloturerEl) : null;
                const $formCloturer = $('#form-cloturer-intervention');
                let cloturerInterventionId = null;
                let cloturerVehiculeCode = null;

                $(document).on('click', '.btn-cloturer-intervention', function () {
                    cloturerInterventionId = $(this).data('id');
                    cloturerVehiculeCode = $(this).data('code');

                    $formCloturer[0].reset();
                    $formCloturer.removeClass('was-validated');
                    $('#cloturer-intervention-code').text(cloturerVehiculeCode);
                    $formCloturer.find('[name=date_fin]').val(new Date().toISOString().slice(0, 10));

                    modalDetail?.hide();
                    modalCloturer.show();
                });

                function envoyerCloture() {
                    $.post(`/flotte/interventions/${cloturerInterventionId}/cloturer`, $formCloturer.serialize())
                        .done(function (res) {
                            modalCloturer.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            const erreurs = xhr.responseJSON?.errors;
                            const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                            Swal.fire({ icon: 'error', text: msg });
                        });
                }

                $formCloturer.on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!$formCloturer[0].checkValidity()) {
                        $formCloturer.addClass('was-validated');
                        return;
                    }

                    // Clôturer = remettre le véhicule en circulation : on le
                    // dit explicitement avant d'envoyer, plutôt que de le
                    // faire silencieusement.
                    Swal.fire({
                        icon: 'question',
                        title: 'Confirmer la clôture',
                        html: `Le véhicule <strong>${cloturerVehiculeCode}</strong> sera remis en circulation. Êtes-vous d'accord ?`,
                        showCancelButton: true,
                        confirmButtonText: 'Oui, confirmer',
                        cancelButtonText: 'Annuler',
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            envoyerCloture();
                        }
                    });
                });
            @endcan

            @can('interventions.type.gerer')
                // --- Gestion des types de panne ---
                const modalTypesEl = document.getElementById('modal-types-panne');
                const modalTypes = modalTypesEl ? new bootstrap.Modal(modalTypesEl) : null;
                const $formType = $('#form-type-panne');

                function resetFormType() {
                    $formType[0].reset();
                    $formType.removeClass('was-validated');
                    $formType.addClass('d-none');
                    $('#type-panne-id').val('');
                }

                $('#btn-gerer-types-panne').on('click', function () {
                    resetFormType();
                    modalTypes.show();
                });

                $('#btn-nouveau-type-panne').on('click', function () {
                    resetFormType();
                    $('#form-type-panne-titre').text('Nouveau type');
                    $formType.removeClass('d-none');
                });

                $('#btn-annuler-type-panne').on('click', resetFormType);

                $('.btn-modifier-type-panne').on('click', function () {
                    resetFormType();
                    $('#form-type-panne-titre').text("Modifier le type");
                    $('#type-panne-id').val($(this).data('id'));
                    $formType.find('[name=libelle]').val($(this).data('libelle'));
                    $('#type-panne-actif').prop('checked', $(this).data('actif') == 1);
                    $formType.removeClass('d-none');
                });

                $formType.on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!$formType[0].checkValidity()) {
                        $formType.addClass('was-validated');
                        return;
                    }

                    const id = $('#type-panne-id').val();
                    const url = id ? `/flotte/interventions/types/${id}` : '/flotte/interventions/types';
                    const data = $formType.serializeArray();
                    if (id) {
                        data.push({ name: '_method', value: 'PUT' });
                    }
                    if (!$('#type-panne-actif').is(':checked')) {
                        data.push({ name: 'actif', value: '0' });
                    }

                    $.post(url, $.param(data))
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            const erreurs = xhr.responseJSON?.errors;
                            const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                            Swal.fire({ icon: 'error', text: msg });
                        });
                });
            @endcan
        });
        </script>
    @endpush
</x-app-layout>
