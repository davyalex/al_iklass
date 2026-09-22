@php
    $badgesStatut = \App\Support\StatutVehiculeBadges::classes();
    $fondsKpiStatut = \App\Support\StatutVehiculeBadges::fondsKpi();
@endphp

<x-app-layout>
    <x-slot name="header">Véhicules</x-slot>

    @include('flotte.partials.statut-styles')

    @if ($fenetreStatut)
        <div class="alert alert-warning d-flex align-items-center py-2 px-3 mb-3" id="banniere-fenetre-statut">
            <i class="bi bi-clock-history me-2"></i><span id="fenetre-statut-texte">—</span>
        </div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-3">
        @foreach ($statuts as $statut)
            <div class="col-6 col-lg">
                <div class="card shadow-sm border-0 {{ $fondsKpiStatut[$statut->code] ?? 'bg-white' }} h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ $statut->libelle }}</div>
                        <div class="h5 mb-0">{{ $kpis['par_statut'][$statut->code] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-6 col-lg">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Recette du jour (en circulation)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['recette_en_circulation'] }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Code</label>
                    <input type="text" id="recherche-vehicule" class="form-control form-control-sm" placeholder="Ex : AL-001">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select id="filtre-statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        @foreach ($statuts as $statut)
                            <option value="{{ $statut->code }}">{{ $statut->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($gestionnaires->isNotEmpty())
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Gestionnaire</label>
                        <select id="filtre-gestionnaire" class="form-select form-select-sm">
                            <option value="">Tous les gestionnaires</option>
                            @foreach ($gestionnaires as $gestionnaire)
                                <option value="{{ $gestionnaire->id }}">{{ $gestionnaire->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-filtrer-vehicule">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-filtre-vehicule" title="Réinitialiser les filtres">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    @can('create', \App\Models\Vehicule::class)
                        <button type="button" class="btn btn-primary ms-md-auto" id="btn-nouveau-vehicule">
                            <i class="bi bi-plus-lg me-1"></i>Nouveau véhicule
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Une grande carte par statut, véhicules affichés en icônes --}}
    <div id="groupes-vehicules">
        @forelse ($statuts as $statut)
            @php $vehiculesDuStatut = $vehiculesParStatut->get($statut->id, collect()); @endphp
            <div class="card shadow-sm border-0 bg-white mb-3 groupe-statut">
                <div class="card-header bg-white border-0 pt-3 d-flex align-items-center gap-2">
                    <span class="badge {{ $badgesStatut[$statut->code] ?? 'bg-secondary' }}">&nbsp;</span>
                    <h2 class="h6 text-uppercase text-muted mb-0">{{ $statut->libelle }}</h2>
                    <span class="badge bg-light text-dark border">{{ $vehiculesDuStatut->count() }}</span>
                </div>
                <div class="card-body pt-2">
                    @if ($vehiculesDuStatut->isEmpty())
                        <p class="small text-muted mb-0 message-vide">Aucun véhicule dans ce statut.</p>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($vehiculesDuStatut as $vehicule)
                                <button type="button"
                                        class="btn btn-outline-secondary chip-vehicule statut-{{ $statut->code }} d-flex flex-column align-items-center gap-1 py-2 btn-voir-vehicule"
                                        data-id="{{ $vehicule->id }}" data-code="{{ strtolower($vehicule->code) }}"
                                        data-statut="{{ $statut->code }}"
                                        data-gestionnaire-id="{{ $vehicule->gestionnaire_id }}">
                                    <i class="bi bi-truck-front fs-3"></i>
                                    <span class="small fw-semibold text-truncate" style="max-width: 100%;">{{ $vehicule->code }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
        @endforelse

        @if ($vehicules->isEmpty())
            <p class="text-muted">Aucun véhicule pour le moment.</p>
        @endif
    </div>

    <x-flotte.vehicule-modals :statuts="$statuts" :gestionnaires="$gestionnaires" :fenetre-statut="$fenetreStatut" :modes-paiement="$modesPaiement" />

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if ($fenetreStatut)
                (function () {
                    const debutFenetre = new Date('{{ $fenetreStatut['debut'] }}');
                    const finFenetre = new Date('{{ $fenetreStatut['fin'] }}');
                    const formatHeure = (date) => date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                    // Déclaré avant le premier appel (qui peut immédiatement conclure
                    // que la fenêtre est fermée et tenter de stopper l'intervalle).
                    let intervalFenetre;

                    function actualiserCompteAReboursFenetre() {
                        const maintenant = new Date();
                        const $banniere = $('#banniere-fenetre-statut');

                        if (maintenant < debutFenetre) {
                            $banniere.removeClass('alert-warning alert-danger').addClass('alert-secondary');
                            $('#fenetre-statut-texte').text(
                                `La modification des statuts ouvre à ${formatHeure(debutFenetre)}.`
                            );
                            return;
                        }

                        if (maintenant > finFenetre) {
                            $banniere.removeClass('alert-warning alert-secondary').addClass('alert-danger');
                            $('#fenetre-statut-texte').text(
                                "La fenêtre de modification des statuts est fermée pour aujourd'hui. Contactez un administrateur si besoin."
                            );
                            clearInterval(intervalFenetre);
                            return;
                        }

                        const diffMs = finFenetre - maintenant;
                        const heures = Math.floor(diffMs / 3600000);
                        const minutes = Math.floor((diffMs % 3600000) / 60000);
                        const reste = heures > 0 ? `${heures} h ${minutes} min` : `${minutes} min`;

                        $banniere.removeClass('alert-secondary alert-danger').addClass('alert-warning');
                        $('#fenetre-statut-texte').text(
                            `Il vous reste ${reste} pour mettre à jour le statut de vos véhicules (fenêtre jusqu'à ${formatHeure(finFenetre)}).`
                        );
                    }

                    actualiserCompteAReboursFenetre();
                    intervalFenetre = setInterval(actualiserCompteAReboursFenetre, 30000);
                })();
            @endif

            function appliquerFiltres() {
                const q = $('#recherche-vehicule').val().trim().toLowerCase();
                const gestionnaireId = $('#filtre-gestionnaire').val() || '';
                const statutCode = $('#filtre-statut').val() || '';
                const filtreActif = !!q || !!gestionnaireId || !!statutCode;

                $('.chip-vehicule').each(function () {
                    // Ces puces portent la classe utilitaire Bootstrap "d-flex"
                    // (display: flex !important) : jQuery .hide()/.toggle() posent
                    // un display:none en style inline, que ce !important ignore
                    // totalement. On masque donc via une classe (d-none, elle
                    // aussi !important et définie après d-flex dans Bootstrap,
                    // donc prioritaire) plutôt que via le style inline.
                    const correspondCode = !q || $(this).data('code').toString().includes(q);
                    const correspondStatut = !statutCode || $(this).data('statut') === statutCode;
                    const correspondGestionnaire = !gestionnaireId || String($(this).data('gestionnaire-id')) === gestionnaireId;
                    $(this).toggleClass('d-none', !(correspondCode && correspondStatut && correspondGestionnaire));
                });

                $('.groupe-statut').each(function () {
                    const $groupe = $(this);
                    const total = $groupe.find('.chip-vehicule').length;

                    if (total === 0) {
                        $groupe.find('.message-vide').toggleClass('d-none', filtreActif);
                        $groupe.toggleClass('d-none', filtreActif);
                        return;
                    }

                    $groupe.toggleClass('d-none', $groupe.find('.chip-vehicule:not(.d-none)').length === 0);
                });

                $('#btn-reset-filtre-vehicule').toggleClass('d-none', !filtreActif);
            }

            $('#btn-filtrer-vehicule').on('click', appliquerFiltres);

            $('#recherche-vehicule').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    appliquerFiltres();
                }
            });

            $('#btn-reset-filtre-vehicule').on('click', function () {
                $('#recherche-vehicule').val('');
                $('#filtre-statut').val('');
                $('#filtre-gestionnaire').val('');
                appliquerFiltres();
            });
        });
        </script>
    @endpush
</x-app-layout>
