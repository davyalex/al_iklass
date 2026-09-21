<x-app-layout>
    <x-slot name="header">Versements</x-slot>

    {{-- KPIs du jour — reflètent le gestionnaire sélectionné dans le filtre ci-dessous (agrégés sur tous les gestionnaires si "Tous") --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Recette journalière</div>
                    <div class="h5 mb-0" id="kpi-recette-journaliere">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-success-subtle h-100">
                <div class="card-body">
                    <div class="small text-muted">Déjà versé (jour)</div>
                    <div class="h5 mb-0" id="kpi-deja-verse-jour">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100" id="carte-reste-a-verser">
                <div class="card-body">
                    <div class="small text-muted">Reste à verser (jour)</div>
                    <div class="h5 mb-0" id="kpi-reste-a-verser-jour">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100" id="carte-montant-du">
                <div class="card-body">
                    <div class="small text-muted">Montant dû</div>
                    <div class="h5 mb-0" id="kpi-montant-du">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
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
                <div class="col-md-3">
                    <label class="form-label small mb-1">Gestionnaire</label>
                    <select name="gestionnaire_id" class="form-select form-select-sm select2-filtre-gestionnaire">
                        <option value="">Tous</option>
                        @foreach ($gestionnaires as $gestionnaire)
                            <option value="{{ $gestionnaire->id }}" @selected(request('gestionnaire_id') == $gestionnaire->id)>{{ $gestionnaire->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Mode</label>
                    <select name="mode_paiement_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($modesPaiement as $mode)
                            <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-versements">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-versements">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
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
                        <th>Gestionnaire</th>
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

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-gestionnaire').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            function rafraichirKpisVersements() {
                $.get('{{ route('flotte.versements.kpis') }}', {
                    gestionnaire_id: $('#filtres-versements [name=gestionnaire_id]').val(),
                }, function (kpis) {
                    $('#kpi-recette-journaliere').text(kpis.recette_journaliere + ' FCFA');
                    $('#kpi-deja-verse-jour').text(kpis.deja_verse_jour + ' FCFA');
                    $('#kpi-reste-a-verser-jour').text(kpis.reste_a_verser_jour + ' FCFA');
                    $('#kpi-montant-du').text(kpis.montant_du + ' FCFA');

                    const resteActif = parseFloat(kpis.reste_a_verser_jour.replace(/\s/g, '').replace(',', '.')) > 0;
                    $('#carte-reste-a-verser').toggleClass('bg-danger-subtle', resteActif).toggleClass('bg-white', !resteActif);

                    const detteActive = parseFloat(kpis.montant_du.replace(/\s/g, '').replace(',', '.')) > 0;
                    $('#carte-montant-du').toggleClass('bg-danger-subtle', detteActive).toggleClass('bg-white', !detteActive);
                });
            }

            rafraichirKpisVersements();
            $('.select2-filtre-gestionnaire').on('change', rafraichirKpisVersements);

            function filtresVersements() {
                return {
                    date_debut: $('#filtres-versements [name=date_debut]').val(),
                    date_fin: $('#filtres-versements [name=date_fin]').val(),
                    gestionnaire_id: $('#filtres-versements [name=gestionnaire_id]').val(),
                    mode_paiement_id: $('#filtres-versements [name=mode_paiement_id]').val(),
                };
            }

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
                    { data: 'gestionnaire_nom', name: 'gestionnaire_nom' },
                    { data: 'vehicule_code', name: 'vehicule_code', defaultContent: '—' },
                    { data: 'montant', name: 'montant' },
                    { data: 'mode_paiement_libelle', name: 'mode_paiement_libelle', orderable: false },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'enregistre_par', name: 'enregistre_par', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-versements').on('click', () => tableVersements.ajax.reload());

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
        });
        </script>
    @endpush
</x-app-layout>
