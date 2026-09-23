<x-app-layout>
    <x-slot name="header">Historique de la dette</x-slot>

    <div class="row g-3 mb-3 row-cols-1 row-cols-sm-2">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Gestionnaires en dette</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-gestionnaires-en-dette">{{ $kpis['gestionnaires_en_dette'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total dû</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-total-du">{{ \App\Support\Money::format($kpis['total_du']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-dettes" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="bascule">Bascule</option>
                        <option value="reglement">Règlement</option>
                        <option value="annulation">Annulation</option>
                    </select>
                </div>
                @if ($gestionnaires->isNotEmpty())
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Gestionnaire</label>
                        <select name="gestionnaire_id" class="form-select form-select-sm select2-filtre-gestionnaire-dette">
                            <option value="">Tous les gestionnaires</option>
                            @foreach ($gestionnaires as $gestionnaire)
                                <option value="{{ $gestionnaire->id }}">{{ $gestionnaire->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-dettes">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-dettes">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="dettes" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($gestionnairesEnDette->isNotEmpty())
        <h2 class="h6 text-uppercase text-muted mb-2">Gestionnaires en dette</h2>

        @foreach ($gestionnairesEnDette as $gestionnaire)
            <div class="card shadow-sm border-0 bg-white mb-3 border-start border-4 border-danger">
                <div class="card-body d-flex align-items-center flex-wrap gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $gestionnaire->name }}</div>
                        <div class="small text-muted">Solde dû</div>
                        <div class="h6 mb-0 text-danger">{{ \App\Support\Money::format($gestionnaire->dette) }} FCFA</div>
                    </div>
                    <div class="d-flex gap-2">
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

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-dettes">
                <thead>
                    <tr>
                        <th>Date</th>
                        @if ($gestionnaires->isNotEmpty())
                            <th>Gestionnaire</th>
                        @endif
                        <th>Type</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Dette après</th>
                        <th>Motif</th>
                        <th>Auteur</th>
                    </tr>
                </thead>
            </table>
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
            const avecColonneGestionnaire = @json($gestionnaires->isNotEmpty());

            $('.select2-filtre-gestionnaire-dette').select2({ width: '100%', selectionCssClass: 'select2-sm' });

            function filtresDettes() {
                return {
                    date_debut: $('#filtres-dettes [name=date_debut]').val(),
                    date_fin: $('#filtres-dettes [name=date_fin]').val(),
                    type: $('#filtres-dettes [name=type]').val(),
                    gestionnaire_id: $('#filtres-dettes [name=gestionnaire_id]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtresDettes()).some((v) => !!v);
                $('#btn-reset-dettes').toggleClass('d-none', !actif);
            }

            $('#filtres-dettes').on('change input', actualiserBoutonReset);

            const colonnes = [
                { data: 'created_at', name: 'created_at' },
            ];
            if (avecColonneGestionnaire) {
                colonnes.push({ data: 'gestionnaire_nom', name: 'gestionnaire_nom', orderable: false });
            }
            colonnes.push(
                { data: 'type_badge', name: 'type', orderable: false },
                { data: 'montant', name: 'montant', className: 'text-end' },
                { data: 'dette_apres_fmt', name: 'dette_apres', className: 'text-end' },
                { data: 'motif', name: 'motif', orderable: false },
                { data: 'auteur', name: 'auteur', orderable: false },
            );

            const tableDettes = $('#table-dettes').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.dettes.data') }}', data: (d) => Object.assign(d, filtresDettes()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: colonnes,
                order: [[0, 'desc']],
            });

            function rafraichirKpis() {
                $.get('{{ route('flotte.dettes.kpis') }}', function (kpis) {
                    $('#kpi-gestionnaires-en-dette').text(kpis.gestionnaires_en_dette);
                    $('#kpi-total-du').text(formatMontant(kpis.total_du) + ' FCFA');
                });
            }

            $('#btn-filtrer-dettes').on('click', () => tableDettes.ajax.reload());

            $('#btn-reset-dettes').on('click', function () {
                $('#filtres-dettes')[0].reset();
                $('.select2-filtre-gestionnaire-dette').val('').trigger('change');
                tableDettes.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresDettes());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-dettes').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.dettes.export.excel') }}');
            });

            $('#btn-export-pdf-dettes').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.dettes.export.pdf') }}');
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
