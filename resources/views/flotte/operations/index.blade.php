<x-app-layout>
    <x-slot name="header">Opérations programmées</x-slot>

    {{-- Filtres --}}
    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Véhicule</label>
                    <select id="filtre-operation-vehicule" class="form-select form-select-sm select2-filtre-operation-vehicule">
                        <option value="">Tous les véhicules</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->id }}">{{ $vehicule->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select id="filtre-operation-type" class="form-select form-select-sm">
                        <option value="">Tous les types</option>
                        @foreach ($typesOperation as $type)
                            <option value="{{ $type->code }}">{{ $type->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Échéance du</label>
                    <input type="date" id="filtre-operation-du" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">au</label>
                    <input type="date" id="filtre-operation-au" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-filtre-operation" title="Réinitialiser les filtres">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <a href="{{ route('flotte.operations.historique.index') }}" class="btn btn-outline-secondary ms-md-auto">
                        <i class="bi bi-clock-history me-1"></i>Historique
                    </a>
                    @can('operations.type.gerer')
                        <button type="button" class="btn btn-outline-secondary" id="btn-gerer-types-operation">
                            <i class="bi bi-tags me-1"></i>Types
                        </button>
                    @endcan
                    @can('operations.gerer')
                        <button type="button" class="btn btn-primary" id="btn-planifier-operation">
                            <i class="bi bi-plus-lg me-1"></i>Planifier
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <style>
        .chip-operation { width: 96px; border-width: 3px; position: relative; }
        .chip-operation .chip-operation-date { font-size: .7rem; }
        .chip-operation.badge-neutre { border-color: #198754 !important; }
        .chip-operation.badge-a_venir { border-color: #ffc107 !important; }
        .chip-operation.badge-jour_j { border-color: #fd7e14 !important; }
        .chip-operation.badge-depasse { border-color: #dc3545 !important; }

        {{-- Marqueur circulaire posé sur le coin de l'icône, plus visible qu'une bordure seule --}}
        .chip-operation-marqueur { position: absolute; top: -6px; right: -6px; width: 16px; height: 16px; border-radius: 50%; border: 2px solid #fff; }
        .chip-operation.badge-a_venir .chip-operation-marqueur { background-color: #ffc107; }
        .chip-operation.badge-jour_j .chip-operation-marqueur { background-color: #fd7e14; }
        .chip-operation.badge-depasse .chip-operation-marqueur { background-color: #dc3545; }

        .bg-jour-j { background-color: #fd7e14; color: #fff; }
    </style>

    {{-- Une grande carte par type d'opération, véhicules programmés affichés en icônes --}}
    <div id="groupes-operations">
        @forelse ($typesOperation as $type)
            @php
                $operationsDuType = $operationsParType->get($type->id, collect());
                $kpi = $kpisParType->firstWhere('type.id', $type->id);
            @endphp
            <div class="card shadow-sm border-0 bg-white mb-3 groupe-type-operation">
                <div class="card-header bg-white border-0 pt-3 d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-tools"></i>
                    <h2 class="h6 text-uppercase text-muted mb-0">{{ $type->libelle }}</h2>
                    <span class="badge bg-light text-dark border">{{ $operationsDuType->count() }}</span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <span class="badge bg-warning text-dark">{{ $kpi['a_venir'] }}</span>
                        <span class="small text-muted">à venir</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <span class="badge bg-jour-j">{{ $kpi['jour_j'] }}</span>
                        <span class="small text-muted">jour J</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <span class="badge bg-danger">{{ $kpi['depasse'] }}</span>
                        <span class="small text-muted">en retard</span>
                    </span>
                </div>
                <div class="card-body pt-2">
                    @if ($operationsDuType->isEmpty())
                        <p class="small text-muted mb-0 message-vide">Aucun véhicule programmé pour ce type.</p>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($operationsDuType as $operation)
                                <button type="button"
                                        class="btn btn-outline-secondary chip-operation badge-{{ $operation->badge() ?? 'neutre' }} d-flex flex-column align-items-center gap-1 py-2 btn-detail-operation"
                                        data-id="{{ $operation->vehicule_id }}" data-vehicule-code="{{ $operation->vehicule_code }}"
                                        data-type-code="{{ $operation->type_operation_code }}"
                                        data-echeance="{{ $operation->date_echeance->toDateString() }}">
                                    @if ($operation->badge())
                                        <span class="chip-operation-marqueur"></span>
                                    @endif
                                    <i class="bi bi-truck-front-fill fs-3"></i>
                                    <span class="small fw-semibold text-truncate" style="max-width: 100%;">{{ $operation->vehicule_code }}</span>
                                    <span class="chip-operation-date text-muted">{{ $operation->date_echeance->format('d/m') }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
        @endforelse

        @if ($typesOperation->isEmpty())
            <p class="text-muted">Aucun type d'opération pour le moment.</p>
        @endif
    </div>

    {{-- Modale détail : programmations actives + historique du véhicule --}}
    <div class="modal fade" id="modal-detail-operation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détail — <span id="detail-operation-code"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="small text-uppercase text-muted">Programmations actives</h6>
                    <div id="detail-operation-actives" class="small mb-3"></div>

                    <h6 class="small text-uppercase text-muted">Historique</h6>
                    <div id="detail-operation-historique" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @can('operations.gerer')
        {{-- Modale planification d'une nouvelle échéance --}}
        <div class="modal fade" id="modal-planifier-operation" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-planifier-operation" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Planifier une opération</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Véhicule</label>
                                <select name="vehicule_id" class="form-select select2-vehicule-po" required>
                                    <option value=""></option>
                                    @foreach ($vehicules as $vehicule)
                                        <option value="{{ $vehicule->id }}">{{ $vehicule->code }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le véhicule est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type d'opération</label>
                                <select name="type_operation_id" class="form-select" id="po-type" required>
                                    <option value=""></option>
                                    @foreach ($typesOperation as $type)
                                        <option value="{{ $type->id }}" data-periodicite="{{ $type->periodicite_jours }}">{{ $type->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le type d'opération est obligatoire.</div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Échéance</label>
                                    <input type="date" name="date_echeance" class="form-control" required>
                                    <div class="invalid-feedback">La date d'échéance est obligatoire.</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Rappel (jours avant)</label>
                                    <input type="number" name="rappel_jours" class="form-control" min="0" value="15" required>
                                    <div class="invalid-feedback">Le rappel est obligatoire.</div>
                                </div>
                            </div>
                            <div class="mb-3 mt-2">
                                <label class="form-label">Périodicité de renouvellement (jours) <span class="text-muted">(optionnel)</span></label>
                                <input type="number" name="periodicite_jours" id="po-periodicite" class="form-control" min="1">
                                <div class="form-text">Une fois réalisée, l'échéance suivante est proposée automatiquement à ce délai. Laisser vide pour une opération ponctuelle.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Commentaire <span class="text-muted">(optionnel)</span></label>
                                <textarea name="commentaire" class="form-control" rows="2" maxlength="500"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Planifier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('operations.type.gerer')
        {{-- Modale gestion des types d'opération (référentiel) --}}
        <div class="modal fade" id="modal-types-operation" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Types d'opération</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Libellé</th>
                                        <th class="text-end">Périodicité (jours)</th>
                                        <th>Statut</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tousLesTypesOperation as $type)
                                        <tr class="{{ $type->actif ? '' : 'text-muted' }}">
                                            <td>{{ $type->libelle }}</td>
                                            <td class="text-end">{{ $type->periodicite_jours ?? '—' }}</td>
                                            <td>
                                                @if ($type->actif)
                                                    <span class="badge bg-success">Actif</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactif</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-type-operation"
                                                        data-id="{{ $type->id }}" data-libelle="{{ $type->libelle }}"
                                                        data-periodicite="{{ $type->periodicite_jours }}" data-actif="{{ $type->actif ? 1 : 0 }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="btn-nouveau-type-operation">
                            <i class="bi bi-plus-lg me-1"></i>Nouveau type
                        </button>

                        <form id="form-type-operation" class="needs-validation border-top pt-3 d-none" novalidate>
                            <input type="hidden" name="id" id="type-operation-id">
                            <h6 class="small text-uppercase text-muted" id="form-type-operation-titre">Nouveau type</h6>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small">Code</label>
                                    <input type="text" name="code" id="type-operation-code" class="form-control form-control-sm" placeholder="ex: vidange" required>
                                    <div class="invalid-feedback">Obligatoire, sans espaces (ex: vidange).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Libellé</label>
                                    <input type="text" name="libelle" class="form-control form-control-sm" required>
                                    <div class="invalid-feedback">Le libellé est obligatoire.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Périodicité (jours)</label>
                                    <input type="number" name="periodicite_jours" class="form-control form-control-sm" min="1">
                                </div>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="actif" id="type-operation-actif" class="form-check-input" value="1" checked>
                                <label class="form-check-label small" for="type-operation-actif">Actif</label>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-annuler-type-operation">Annuler</button>
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
            $('.select2-filtre-operation-vehicule').select2({ width: '100%', selectionCssClass: 'select2-sm' });

            function appliquerFiltresOperations() {
                const vehiculeId = $('#filtre-operation-vehicule').val() || '';
                const typeCode = $('#filtre-operation-type').val() || '';
                const du = $('#filtre-operation-du').val();
                const au = $('#filtre-operation-au').val();
                const filtreActif = !!vehiculeId || !!typeCode || !!du || !!au;

                $('.chip-operation').each(function () {
                    const $chip = $(this);
                    const correspondVehicule = !vehiculeId || String($chip.data('id')) === vehiculeId;
                    const correspondType = !typeCode || $chip.data('type-code') === typeCode;
                    const echeance = String($chip.data('echeance'));
                    const correspondDate = (!du || echeance >= du) && (!au || echeance <= au);
                    $chip.toggleClass('d-none', !(correspondVehicule && correspondType && correspondDate));
                });

                $('.groupe-type-operation').each(function () {
                    const $groupe = $(this);
                    const total = $groupe.find('.chip-operation').length;

                    if (total === 0) {
                        $groupe.find('.message-vide').toggleClass('d-none', filtreActif);
                        $groupe.toggleClass('d-none', filtreActif);
                        return;
                    }

                    $groupe.toggleClass('d-none', $groupe.find('.chip-operation:not(.d-none)').length === 0);
                });

                $('#btn-reset-filtre-operation').toggleClass('d-none', !filtreActif);
            }

            $('#filtre-operation-vehicule, #filtre-operation-type, #filtre-operation-du, #filtre-operation-au').on('change', appliquerFiltresOperations);

            $('#btn-reset-filtre-operation').on('click', function () {
                $('#filtre-operation-type').val('');
                $('#filtre-operation-du').val('');
                $('#filtre-operation-au').val('');
                $('.select2-filtre-operation-vehicule').val('').trigger('change');
                appliquerFiltresOperations();
            });

            // --- Détail ---
            const modalDetailEl = document.getElementById('modal-detail-operation');
            const modalDetail = modalDetailEl ? new bootstrap.Modal(modalDetailEl) : null;

            function badgeCouleur(badge) {
                if (badge === 'depasse') return 'bg-danger';
                if (badge === 'jour_j') return 'bg-jour-j';
                if (badge === 'a_venir') return 'bg-warning text-dark';
                return 'bg-secondary';
            }

            function chargerDetail(vehiculeId, vehiculeCode) {
                $('#detail-operation-code').text(vehiculeCode);
                $('#detail-operation-actives').html('<p class="text-muted mb-0">Chargement…</p>');
                $('#detail-operation-historique').html('');

                $.get(`/flotte/operations/vehicules/${vehiculeId}/detail`, function (res) {
                    if (!res.actives.length) {
                        $('#detail-operation-actives').html('<p class="text-muted mb-0">Aucune programmation active.</p>');
                    } else {
                        $('#detail-operation-actives').html(res.actives.map(function (o) {
                            const boutonRealiser = @json(auth()->user()->can('operations.realiser'))
                                ? `<button type="button" class="btn btn-sm btn-success btn-realiser-operation" data-id="${o.id}" data-type="${o.type_operation_libelle}">Réaliser</button>`
                                : '';
                            return `<div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <span class="badge ${badgeCouleur(o.badge)}">${o.type_operation_libelle}</span>
                                    <span class="ms-1">Échéance : <strong>${o.date_echeance}</strong></span>
                                    <span class="text-muted ms-1">(rappel ${o.rappel_jours}j avant)</span>
                                </div>
                                ${boutonRealiser}
                            </div>`;
                        }).join(''));
                    }

                    if (!res.historique.length) {
                        $('#detail-operation-historique').html('<p class="text-muted mb-0">Aucun historique pour ce véhicule.</p>');
                    } else {
                        $('#detail-operation-historique').html(res.historique.map(function (o) {
                            return `<div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                <div>
                                    <span class="badge bg-light text-dark border">${o.type_operation_libelle}</span>
                                    <span class="text-muted">réalisée le ${o.date_realisation}</span>
                                    ${o.realise_par ? ' <span class="text-muted">— ' + o.realise_par + '</span>' : ''}
                                    ${o.commentaire ? '<br><span class="text-muted">' + o.commentaire + '</span>' : ''}
                                </div>
                            </div>`;
                        }).join(''));
                    }

                    modalDetail.show();
                });
            }

            $(document).on('click', '.btn-detail-operation', function () {
                chargerDetail($(this).data('id'), $(this).data('vehicule-code'));
            });

            // --- Réaliser (clôture + renouvellement automatique) ---
            $(document).on('click', '.btn-realiser-operation', function () {
                const id = $(this).data('id');
                const type = $(this).data('type');

                Swal.fire({
                    icon: 'question',
                    title: `Marquer "${type}" comme réalisée ?`,
                    text: 'La prochaine échéance sera calculée automatiquement.',
                    input: 'textarea',
                    inputPlaceholder: 'Commentaire (optionnel)',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmer',
                    cancelButtonText: 'Annuler',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.post(`/flotte/operations/${id}/realiser`, { commentaire: result.value || null })
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            @can('operations.gerer')
                // --- Planifier ---
                $('.select2-vehicule-po').select2({ width: '100%', dropdownParent: $('#modal-planifier-operation') });

                const modalPlanifierEl = document.getElementById('modal-planifier-operation');
                const modalPlanifier = modalPlanifierEl ? new bootstrap.Modal(modalPlanifierEl) : null;
                const $formPlanifier = $('#form-planifier-operation');

                $('#btn-planifier-operation').on('click', function () {
                    $formPlanifier[0].reset();
                    $formPlanifier.removeClass('was-validated');
                    $('.select2-vehicule-po').val('').trigger('change');
                    modalPlanifier.show();
                });

                $('#po-type').on('change', function () {
                    const periodicite = $(this).find(':selected').data('periodicite');
                    if (periodicite && !$('#po-periodicite').val()) {
                        $('#po-periodicite').val(periodicite);
                    }
                });

                $formPlanifier.on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!$formPlanifier[0].checkValidity()) {
                        $formPlanifier.addClass('was-validated');
                        return;
                    }

                    $.post('{{ route('flotte.operations.store') }}', $formPlanifier.serialize())
                        .done(function (res) {
                            modalPlanifier.hide();
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

            @can('operations.type.gerer')
                // --- Gestion des types d'opération ---
                const modalTypesEl = document.getElementById('modal-types-operation');
                const modalTypes = modalTypesEl ? new bootstrap.Modal(modalTypesEl) : null;
                const $formType = $('#form-type-operation');

                function resetFormType() {
                    $formType[0].reset();
                    $formType.removeClass('was-validated');
                    $formType.addClass('d-none');
                    $('#type-operation-id').val('');
                    $('#type-operation-code').prop('disabled', false);
                }

                $('#btn-gerer-types-operation').on('click', function () {
                    resetFormType();
                    modalTypes.show();
                });

                $('#btn-nouveau-type-operation').on('click', function () {
                    resetFormType();
                    $('#form-type-operation-titre').text('Nouveau type');
                    $formType.removeClass('d-none');
                });

                $('#btn-annuler-type-operation').on('click', resetFormType);

                $('.btn-modifier-type-operation').on('click', function () {
                    resetFormType();
                    $('#form-type-operation-titre').text("Modifier le type");
                    $('#type-operation-id').val($(this).data('id'));
                    $('#type-operation-code').prop('disabled', true);
                    $formType.find('[name=code]').val('(non modifiable)');
                    $formType.find('[name=libelle]').val($(this).data('libelle'));
                    $formType.find('[name=periodicite_jours]').val($(this).data('periodicite'));
                    $('#type-operation-actif').prop('checked', $(this).data('actif') == 1);
                    $formType.removeClass('d-none');
                });

                $formType.on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!$formType[0].checkValidity()) {
                        $formType.addClass('was-validated');
                        return;
                    }

                    const id = $('#type-operation-id').val();
                    const url = id ? `/flotte/operations/types/${id}` : '/flotte/operations/types';
                    const data = $formType.serializeArray().filter((f) => f.name !== 'code' || !id);
                    if (id) {
                        data.push({ name: '_method', value: 'PUT' });
                    }
                    if (!$('#type-operation-actif').is(':checked')) {
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
