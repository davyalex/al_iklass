<x-app-layout>
    <x-slot name="header">Versements</x-slot>

    @can('create', \App\Models\Versement::class)
        <div class="al-page-actions">
            <button type="button" class="btn btn-primary" id="btn-nouveau-versement">
                <i class="bi bi-plus-lg me-1"></i>Nouveau versement
            </button>
        </div>
    @endcan

    {{-- KPIs du jour — reflètent le gestionnaire sélectionné dans le filtre ci-dessous (agrégés sur tous les gestionnaires si "Tous") --}}
    <div class="row g-3 mb-3 row-cols-2 row-cols-lg-5">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Recette journalière</div>
                    <div class="h5 mb-0" id="kpi-recette-journaliere">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-success-subtle h-100">
                <div class="card-body">
                    <div class="small text-muted">Déjà versé (jour)</div>
                    <div class="h5 mb-0" id="kpi-deja-verse-jour">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" id="carte-reste-a-verser">
                <div class="card-body">
                    <div class="small text-muted">Reste à verser (jour)</div>
                    <div class="h5 mb-0" id="kpi-reste-a-verser-jour">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" id="carte-montant-du">
                <div class="card-body">
                    <div class="small text-muted">Montant dû</div>
                    <div class="h5 mb-0" id="kpi-montant-du">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total versé (période)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-total-verse-periode">—</div>
                </div>
            </div>
        </div>
    </div>
    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i>« Total versé » suit le filtre de période ci-dessous (mois en cours par défaut). Les autres indicateurs restent sur la journée en cours.
    </p>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-versements" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
                </div>
                @if ($peutVoirTout)
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Gestionnaire</label>
                        <select name="gestionnaire_id" class="form-select form-select-sm select2-filtre-gestionnaire">
                            <option value="">Tous</option>
                            @foreach ($gestionnaires as $gestionnaire)
                                <option value="{{ $gestionnaire->id }}" @selected(request('gestionnaire_id') == $gestionnaire->id)>{{ $gestionnaire->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Mode</label>
                    <select name="mode_paiement_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($modesPaiement as $mode)
                            <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 al-filtres-actions">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-versements">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <x-filtre-reset id="btn-reset-versements" />
                    <div class="ms-auto">
                        <x-export-dropdown id-suffix="versements" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-versements">
                <thead>
                    <tr>
                        <th>Date</th>
                        @if ($peutVoirTout)
                            <th>Gestionnaire</th>
                        @endif
                        <th>Véhicule</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Référence</th>
                        <th>Enregistré par</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale nouveau versement --}}
    <div class="modal fade" id="modal-versement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-versement">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau versement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if ($peutVoirTout)
                            <div class="mb-3">
                                <label class="form-label">Gestionnaire</label>
                                <select name="gestionnaire_id" class="form-select select2-gestionnaire-versement" required>
                                    <option value=""></option>
                                    @foreach ($gestionnaires as $gestionnaire)
                                        <option value="{{ $gestionnaire->id }}">{{ $gestionnaire->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Véhicule <span class="text-muted">(optionnel)</span></label>
                            <select name="vehicule_id" class="form-select select2-vehicule-versement">
                                <option value=""></option>
                                @foreach ($vehicules as $vehicule)
                                    <option value="{{ $vehicule->id }}" data-gestionnaire-id="{{ $vehicule->gestionnaire_id ?? '' }}">{{ $vehicule->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text mb-2" id="versement-reste-a-verser-info"></div>
                        <div class="mb-3">
                            <label class="form-label d-block">Type de versement</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type_versement" id="versement-type-total" checked>
                                <label class="btn btn-outline-primary" for="versement-type-total">Versement total</label>
                                <input type="radio" class="btn-check" name="type_versement" id="versement-type-partiel">
                                <label class="btn btn-outline-primary" for="versement-type-partiel">Versement partiel</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant (FCFA)</label>
                            <input type="number" name="montant" id="versement-montant" class="form-control" min="0.01" step="0.01" required>
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

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-gestionnaire').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            function filtresVersements() {
                return {
                    date_debut: $('#filtres-versements [name=date_debut]').val(),
                    date_fin: $('#filtres-versements [name=date_fin]').val(),
                    gestionnaire_id: $('#filtres-versements [name=gestionnaire_id]').val(),
                    mode_paiement_id: $('#filtres-versements [name=mode_paiement_id]').val(),
                };
            }

            function rafraichirKpisVersements() {
                $.get('{{ route('flotte.versements.kpis') }}', {
                    gestionnaire_id: $('#filtres-versements [name=gestionnaire_id]').val(),
                    date_debut: $('#filtres-versements [name=date_debut]').val(),
                    date_fin: $('#filtres-versements [name=date_fin]').val(),
                }, function (kpis) {
                    $('#kpi-recette-journaliere').text(kpis.recette_journaliere + ' FCFA');
                    $('#kpi-deja-verse-jour').text(kpis.deja_verse_jour + ' FCFA');
                    $('#kpi-reste-a-verser-jour').text(kpis.reste_a_verser_jour + ' FCFA');
                    $('#kpi-montant-du').text(kpis.montant_du + ' FCFA');
                    $('#kpi-total-verse-periode').text(kpis.total_verse_periode + ' FCFA');

                    const resteActif = parseFloat(kpis.reste_a_verser_jour.replace(/\s/g, '').replace(',', '.')) > 0;
                    $('#carte-reste-a-verser').toggleClass('bg-danger-subtle', resteActif).toggleClass('bg-white', !resteActif);

                    const detteActive = parseFloat(kpis.montant_du.replace(/\s/g, '').replace(',', '.')) > 0;
                    $('#carte-montant-du').toggleClass('bg-danger-subtle', detteActive).toggleClass('bg-white', !detteActive);
                });
            }

            rafraichirKpisVersements();
            $('.select2-filtre-gestionnaire').on('change', rafraichirKpisVersements);

            const DATE_DEBUT_DEFAUT = $('#filtres-versements [name=date_debut]').val();
            const DATE_FIN_DEFAUT = $('#filtres-versements [name=date_fin]').val();

            function actualiserBoutonResetVersements() {
                const f = filtresVersements();
                const actif = Boolean(f.gestionnaire_id || f.mode_paiement_id)
                    || f.date_debut !== DATE_DEBUT_DEFAUT
                    || f.date_fin !== DATE_FIN_DEFAUT;
                $('#btn-reset-versements').toggleClass('d-none', !actif);
            }

            $('#filtres-versements').on('change input', actualiserBoutonResetVersements);
            actualiserBoutonResetVersements();

            const tableVersements = $('#table-versements').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('flotte.versements.data') }}',
                    data: (d) => Object.assign(d, filtresVersements()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_versement', name: 'date_versement' },
                    @if ($peutVoirTout)
                        { data: 'gestionnaire_nom', name: 'gestionnaire_nom' },
                    @endif
                    { data: 'vehicule_code', name: 'vehicule_code', defaultContent: '—' },
                    { data: 'montant', name: 'montant' },
                    { data: 'mode_paiement_libelle', name: 'mode_paiement_libelle', orderable: false },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'enregistre_par', name: 'enregistre_par', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-versements').on('click', function () {
                tableVersements.ajax.reload();
                rafraichirKpisVersements();
            });

            $('#btn-reset-versements').on('click', function () {
                $('#filtres-versements')[0].reset();
                $('.select2-filtre-gestionnaire').val('').trigger('change');
                tableVersements.ajax.reload();
                actualiserBoutonResetVersements();
                rafraichirKpisVersements();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresVersements());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-versements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.versements.export.excel') }}');
            });

            $('#btn-export-pdf-versements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('flotte.versements.export.pdf') }}');
            });

            const modalVersement = new bootstrap.Modal('#modal-versement');

            $('.select2-vehicule-versement').select2({ dropdownParent: $('#modal-versement'), width: '100%', placeholder: '—' });

            // Bascule Versement total / partiel : en "total", le montant est
            // verrouillé sur le reste à verser (jour) du gestionnaire concerné ;
            // en "partiel", le champ se vide et devient modifiable.
            function appliquerTypeVersement() {
                const reste = $('#form-versement').data('reste-a-verser') || 0;

                if ($('#versement-type-total').is(':checked')) {
                    $('#versement-montant').val(reste > 0 ? reste : '').prop('readonly', true);
                } else {
                    $('#versement-montant').val('').prop('readonly', false).trigger('focus');
                }
            }

            function chargerResteAVerser(gestionnaireId) {
                $('#form-versement').removeData('reste-a-verser');
                $('#versement-reste-a-verser-info').text('Chargement…');

                $.get('{{ route('flotte.versements.kpis') }}', gestionnaireId ? { gestionnaire_id: gestionnaireId } : {}, function (kpis) {
                    const reste = parseFloat(kpis.reste_a_verser_jour.replace(/\s/g, '').replace(',', '.')) || 0;
                    $('#form-versement').data('reste-a-verser', reste);
                    $('#versement-reste-a-verser-info').text('Reste à verser (jour) : ' + kpis.reste_a_verser_jour + ' FCFA');
                    appliquerTypeVersement();
                });
            }

            $('#modal-versement input[name=type_versement]').on('change', appliquerTypeVersement);

            @if ($peutVoirTout)
                $('.select2-gestionnaire-versement').select2({ dropdownParent: $('#modal-versement'), width: '100%' });

                // Le véhicule proposé dépend du gestionnaire choisi (un seul
                // gestionnaire à la fois) : on grise les autres options.
                function actualiserVehiculesDisponibles() {
                    const gestionnaireId = $('#form-versement [name=gestionnaire_id]').val();
                    $('.select2-vehicule-versement option').each(function () {
                        const appartientA = $(this).data('gestionnaireId');
                        $(this).prop('disabled', this.value !== '' && gestionnaireId && String(appartientA) !== String(gestionnaireId));
                    });
                }

                $('.select2-gestionnaire-versement').on('change', function () {
                    $('.select2-vehicule-versement').val('').trigger('change');
                    actualiserVehiculesDisponibles();

                    const gestionnaireId = $(this).val();
                    if (gestionnaireId) {
                        chargerResteAVerser(gestionnaireId);
                    } else {
                        $('#form-versement').removeData('reste-a-verser');
                        $('#versement-reste-a-verser-info').text("Choisissez d'abord un gestionnaire.");
                        $('#versement-montant').val('').prop('readonly', false);
                    }
                });
            @endif

            $('#btn-nouveau-versement').on('click', function () {
                $('#form-versement')[0].reset();
                $('.select2-gestionnaire-versement, .select2-vehicule-versement').val('').trigger('change');
                $('#versement-montant').prop('readonly', false);

                @if ($peutVoirTout)
                    $('#versement-reste-a-verser-info').text("Choisissez d'abord un gestionnaire.");
                @else
                    chargerResteAVerser();
                @endif

                modalVersement.show();
            });

            $('#form-versement').on('submit', function (e) {
                e.preventDefault();

                $.post('{{ route('flotte.versements.store') }}', $(this).serialize())
                    .done(function (res) {
                        modalVersement.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 2200, showConfirmButton: false });
                        tableVersements.ajax.reload();
                        rafraichirKpisVersements();
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
