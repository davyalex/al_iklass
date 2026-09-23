@php
    $badgesStatut = \App\Support\StatutVehiculeBadges::classes();
    $fondsKpiStatut = \App\Support\StatutVehiculeBadges::fondsKpi();
@endphp

<x-app-layout>
    <x-slot name="header">État du parc</x-slot>

    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('flotte.etat-parc.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                </div>
                @if ($gestionnaires->isNotEmpty())
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Gestionnaire</label>
                        <select name="gestionnaire_id" class="form-select form-select-sm select2-filtre-gestionnaire-parc">
                            <option value="">Tous les gestionnaires</option>
                            @foreach ($gestionnaires as $gestionnaire)
                                <option value="{{ $gestionnaire->id }}" {{ request()->integer('gestionnaire_id') === $gestionnaire->id ? 'selected' : '' }}>{{ $gestionnaire->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i>Afficher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <p class="text-muted small mb-3">
        Situation du parc au <strong>{{ $date->format('d/m/Y') }}</strong>, reconstituée depuis l'historique des changements de statut.
        @if ($vehiculesInexistants > 0)
            {{ $vehiculesInexistants }} véhicule(s) n'existai{{ $vehiculesInexistants > 1 ? 'en' : '' }}t pas encore à cette date et {{ $vehiculesInexistants > 1 ? 'sont' : 'est' }} exclu{{ $vehiculesInexistants > 1 ? 's' : '' }}.
        @endif
    </p>

    {{-- KPIs --}}
    <div class="row g-3 mb-3">
        @foreach ($statuts as $statut)
            <div class="col-6 col-lg">
                <div class="card shadow-sm border-0 {{ $fondsKpiStatut[$statut->code] ?? 'bg-white' }} h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ $statut->libelle }}</div>
                        <div class="h5 mb-0">{{ $kpisParStatut[$statut->code] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Situation financière par gestionnaire, à la même date --}}
    @if ($situationFinanciere->isNotEmpty())
        <div class="card shadow-sm border-0 bg-white mb-3">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h6 text-uppercase text-muted mb-0">Situation financière au {{ $date->format('d/m/Y') }}</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Gestionnaire</th>
                            <th class="text-end">Attendu</th>
                            <th class="text-end">Déjà versé</th>
                            <th class="text-end">Reste à verser</th>
                            <th>Statut</th>
                            <th class="text-end">Solde dette (actuel)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($situationFinanciere as $ligne)
                            <tr>
                                <td>{{ $ligne['gestionnaire']->name }}</td>
                                <td class="text-end">{{ \App\Support\Money::format($ligne['attendu']) }}</td>
                                <td class="text-end">{{ \App\Support\Money::format($ligne['deja_verse']) }}</td>
                                <td class="text-end {{ $ligne['reste_a_verser'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ \App\Support\Money::format($ligne['reste_a_verser']) }}</td>
                                <td>
                                    @if ($ligne['a_jour'])
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>À jour</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Versement en attente</span>
                                    @endif
                                    @if ($ligne['solde_dette'] > 0)
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i>Dette</span>
                                    @endif
                                </td>
                                <td class="text-end {{ $ligne['solde_dette'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ \App\Support\Money::format($ligne['solde_dette']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Une carte par statut, véhicules affichés en icônes --}}
    <div id="groupes-etat-parc">
        @forelse ($statuts as $statut)
            @php $vehiculesDuStatut = $vehiculesParStatut->get($statut->code, collect()); @endphp
            <div class="card shadow-sm border-0 bg-white mb-3">
                <div class="card-header bg-white border-0 pt-3 d-flex align-items-center gap-2">
                    <span class="badge {{ $badgesStatut[$statut->code] ?? 'bg-secondary' }}">&nbsp;</span>
                    <h2 class="h6 text-uppercase text-muted mb-0">{{ $statut->libelle }}</h2>
                    <span class="badge bg-light text-dark border">{{ $vehiculesDuStatut->count() }}</span>
                </div>
                <div class="card-body pt-2">
                    @if ($vehiculesDuStatut->isEmpty())
                        <p class="small text-muted mb-0">Aucun véhicule dans ce statut à cette date.</p>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($vehiculesDuStatut as $vehicule)
                                <button type="button"
                                        class="btn btn-outline-secondary chip-vehicule statut-{{ $statut->code }} d-flex flex-column align-items-center gap-1 py-2 btn-voir-vehicule"
                                        data-id="{{ $vehicule->id }}">
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
    </div>

    <x-flotte.vehicule-modals :statuts="$statuts" :gestionnaires="$gestionnaires" />

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-gestionnaire-parc').select2({ width: '100%', placeholder: 'Tous les gestionnaires' });
        });
        </script>
    @endpush
</x-app-layout>
