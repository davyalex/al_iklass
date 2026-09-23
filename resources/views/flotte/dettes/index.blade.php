<x-app-layout>
    <x-slot name="header">Dette</x-slot>

    <div class="row g-3 mb-3 row-cols-1 row-cols-sm-2">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Gestionnaires en dette</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['gestionnaires_en_dette'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total dû</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($kpis['total_du']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    @if ($gestionnairesEnDette->isEmpty())
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                Aucun gestionnaire en dette pour le moment.
            </div>
        </div>
    @else
        @foreach ($gestionnairesEnDette as $gestionnaire)
            <div class="card shadow-sm border-0 bg-white mb-3 border-start border-4 border-danger">
                <div class="card-body d-flex align-items-center flex-wrap gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $gestionnaire->name }}</div>
                        <div class="small text-muted">Solde dû</div>
                        <div class="h6 mb-0 text-danger">{{ \App\Support\Money::format($gestionnaire->dette) }} FCFA</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-detail-dette" data-id="{{ $gestionnaire->id }}">
                            <i class="bi bi-eye me-1"></i>Détail
                        </button>
                        @can('flotte.dette.regler')
                            <button type="button" class="btn btn-sm btn-success btn-regler-dette" data-id="{{ $gestionnaire->id }}" data-nom="{{ $gestionnaire->name }}" data-solde="{{ (float) $gestionnaire->dette }}">
                                <i class="bi bi-cash-coin me-1"></i>Régler
                            </button>
                        @endcan
                        @can('flotte.dette.gerer')
                            <button type="button" class="btn btn-sm btn-outline-danger btn-annuler-dette" data-id="{{ $gestionnaire->id }}" data-nom="{{ $gestionnaire->name }}" data-solde="{{ (float) $gestionnaire->dette }}">
                                <i class="bi bi-x-circle me-1"></i>Annuler
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    {{-- Modale détail : jours ayant généré de la dette + règlements/annulations --}}
    <div class="modal fade" id="modal-detail-dette" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Détail — <span id="detail-dette-nom"></span></h5>
                        <span class="small text-muted">Solde dû : <strong id="detail-dette-solde"></strong></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="small text-uppercase text-muted">Jours ayant généré de la dette</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Montant à verser</th>
                                    <th class="text-end">Montant versé</th>
                                    <th class="text-end">Reste (dette)</th>
                                </tr>
                            </thead>
                            <tbody id="detail-dette-jours"></tbody>
                        </table>
                    </div>

                    <div id="detail-dette-mouvements-bloc" class="d-none">
                        <h6 class="small text-uppercase text-muted">Règlements et annulations</h6>
                        <div id="detail-dette-mouvements" class="small"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale règlement de dette --}}
    <div class="modal fade" id="modal-regler-dette" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-regler-dette" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Régler la dette — <span id="regler-dette-nom"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label d-block">Type de règlement</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type_reglement" id="rd-type-total" checked>
                                <label class="btn btn-outline-success" for="rd-type-total">Total</label>
                                <input type="radio" class="btn-check" name="type_reglement" id="rd-type-partiel">
                                <label class="btn btn-outline-success" for="rd-type-partiel">Partiel</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant (FCFA)</label>
                            <input type="number" name="montant" id="regler-dette-montant" class="form-control" min="0.01" step="0.01" required>
                            <div class="invalid-feedback">Le montant doit être positif et ne peut pas dépasser la dette actuelle.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif <span class="text-muted">(optionnel)</span></label>
                            <input type="text" name="motif" class="form-control" maxlength="500">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Confirmer le règlement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @can('flotte.dette.gerer')
        {{-- Modale annulation de dette (totale ou partielle) --}}
        <div class="modal fade" id="modal-annuler-dette" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-annuler-dette" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Annuler la dette — <span id="annuler-dette-nom"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label d-block">Type d'annulation</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type_annulation_dette" id="ad-type-total" checked>
                                    <label class="btn btn-outline-danger" for="ad-type-total">Totale</label>
                                    <input type="radio" class="btn-check" name="type_annulation_dette" id="ad-type-partiel">
                                    <label class="btn btn-outline-danger" for="ad-type-partiel">Partielle</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Montant (FCFA)</label>
                                <input type="number" name="montant" id="annuler-dette-montant" class="form-control" min="0.01" step="0.01" required>
                                <div class="invalid-feedback">Le montant doit être positif et ne peut pas dépasser la dette actuelle.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Motif</label>
                                <textarea name="motif" class="form-control" rows="3" required maxlength="500"></textarea>
                                <div class="invalid-feedback">Le motif est obligatoire.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger">Confirmer l'annulation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Détail ---
            const modalDetailEl = document.getElementById('modal-detail-dette');
            const modalDetail = modalDetailEl ? new bootstrap.Modal(modalDetailEl) : null;

            $(document).on('click', '.btn-detail-dette', function () {
                const id = $(this).data('id');

                $('#detail-dette-jours').html('<tr><td colspan="4" class="text-muted">Chargement…</td></tr>');
                $('#detail-dette-mouvements-bloc').addClass('d-none');

                $.get(`/flotte/gestionnaires/${id}/dette/detail`, function (res) {
                    $('#detail-dette-nom').text(res.gestionnaire.name);
                    $('#detail-dette-solde').text(res.solde_du + ' FCFA');

                    if (!res.jours.length) {
                        $('#detail-dette-jours').html('<tr><td colspan="4" class="text-muted">Aucun jour en dette.</td></tr>');
                    } else {
                        $('#detail-dette-jours').html(res.jours.map(function (j) {
                            return `<tr>
                                <td>${j.date}</td>
                                <td class="text-end">${j.attendu} FCFA</td>
                                <td class="text-end">${j.deja_verse} FCFA</td>
                                <td class="text-end text-danger fw-semibold">${j.reste} FCFA</td>
                            </tr>`;
                        }).join(''));
                    }

                    if (res.mouvements.length) {
                        $('#detail-dette-mouvements-bloc').removeClass('d-none');
                        $('#detail-dette-mouvements').html(res.mouvements.map(function (m) {
                            const badge = m.type === 'reglement'
                                ? '<span class="badge bg-success">Règlement</span>'
                                : '<span class="badge bg-secondary">Annulation</span>';
                            return `<div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                <div>
                                    ${badge} <strong>${m.montant} FCFA</strong>
                                    ${m.auteur ? ' <span class="text-muted">— ' + m.auteur + '</span>' : ''}
                                    ${m.motif ? '<br><span class="text-muted">' + m.motif + '</span>' : ''}
                                </div>
                                <div class="text-muted text-nowrap ms-2">${m.date}</div>
                            </div>`;
                        }).join(''));
                    }

                    modalDetail.show();
                });
            });

            // --- Règlement ---
            const modalReglerEl = document.getElementById('modal-regler-dette');
            const modalRegler = modalReglerEl ? new bootstrap.Modal(modalReglerEl) : null;
            const $formRegler = $('#form-regler-dette');
            let reglerGestionnaireId = null;

            function appliquerTypeReglement() {
                const solde = $formRegler.data('solde') || 0;
                if ($('#rd-type-total').is(':checked')) {
                    $('#regler-dette-montant').val(solde > 0 ? solde.toFixed(2) : '').prop('readonly', true);
                } else {
                    $('#regler-dette-montant').val('').prop('readonly', false).trigger('focus');
                }
            }

            $('#modal-regler-dette input[name=type_reglement]').on('change', appliquerTypeReglement);

            $(document).on('click', '.btn-regler-dette', function () {
                reglerGestionnaireId = $(this).data('id');
                const solde = parseFloat($(this).data('solde')) || 0;

                $formRegler[0].reset();
                $formRegler.removeClass('was-validated');
                $formRegler.data('solde', solde);
                $('#regler-dette-nom').text($(this).data('nom'));
                $('#rd-type-total').prop('checked', true);
                $('#regler-dette-montant').attr('max', solde);
                appliquerTypeReglement();
                modalRegler.show();
            });

            $formRegler.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!$formRegler[0].checkValidity()) {
                    $formRegler.addClass('was-validated');
                    return;
                }

                $.post(`/flotte/gestionnaires/${reglerGestionnaireId}/dette/regler`, $formRegler.serialize())
                    .done(function (res) {
                        modalRegler.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const erreurs = xhr.responseJSON?.errors;
                        const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            // --- Annulation (admin) ---
            const modalAnnulerEl = document.getElementById('modal-annuler-dette');
            const modalAnnuler = modalAnnulerEl ? new bootstrap.Modal(modalAnnulerEl) : null;
            const $formAnnuler = $('#form-annuler-dette');
            let annulerGestionnaireId = null;

            function appliquerTypeAnnulation() {
                const solde = $formAnnuler.data('solde') || 0;
                if ($('#ad-type-total').is(':checked')) {
                    $('#annuler-dette-montant').val(solde > 0 ? solde.toFixed(2) : '').prop('readonly', true);
                } else {
                    $('#annuler-dette-montant').val('').prop('readonly', false).trigger('focus');
                }
            }

            $('#modal-annuler-dette input[name=type_annulation_dette]').on('change', appliquerTypeAnnulation);

            $(document).on('click', '.btn-annuler-dette', function () {
                annulerGestionnaireId = $(this).data('id');
                const solde = parseFloat($(this).data('solde')) || 0;

                $formAnnuler[0].reset();
                $formAnnuler.removeClass('was-validated');
                $formAnnuler.data('solde', solde);
                $('#annuler-dette-nom').text($(this).data('nom'));
                $('#ad-type-total').prop('checked', true);
                $('#annuler-dette-montant').attr('max', solde);
                appliquerTypeAnnulation();
                modalAnnuler?.show();
            });

            $formAnnuler.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!$formAnnuler[0].checkValidity()) {
                    $formAnnuler.addClass('was-validated');
                    return;
                }

                $.post(`/flotte/gestionnaires/${annulerGestionnaireId}/dette/annuler`, $formAnnuler.serialize())
                    .done(function (res) {
                        modalAnnuler.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const erreurs = xhr.responseJSON?.errors;
                        const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });
        });
        </script>
    @endpush
</x-app-layout>
