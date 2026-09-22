@php
    $badgesStatut = \App\Support\StatutVehiculeBadges::classes();
@endphp

<x-app-layout>
    <x-slot name="header">Rapport véhicule — {{ $vehicule->code }}</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <a href="{{ route('flotte.vehicules.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux véhicules
        </a>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <h2 class="h5 mb-0">{{ $vehicule->code }} — {{ $vehicule->libelle }}</h2>
                <span class="badge {{ $badgesStatut[$vehicule->statut?->code] ?? 'bg-secondary' }}">{{ $vehicule->statut?->libelle ?? 'Sans statut' }}</span>
            </div>
            <dl class="row mb-0 small">
                <dt class="col-6 col-md-3">Marque / Modèle</dt>
                <dd class="col-6 col-md-3">{{ trim(($vehicule->marque ?? '').' '.($vehicule->modele ?? '')) ?: '—' }}</dd>
                <dt class="col-6 col-md-3">Immatriculation</dt>
                <dd class="col-6 col-md-3">{{ $vehicule->immatriculation ?? '—' }}</dd>
                <dt class="col-6 col-md-3">Chauffeur</dt>
                <dd class="col-6 col-md-3">{{ trim(($vehicule->chauffeur_nom ?? '').' '.($vehicule->chauffeur_telephone ? '— '.$vehicule->chauffeur_telephone : '')) ?: '—' }}</dd>
                <dt class="col-6 col-md-3">Gestionnaire</dt>
                <dd class="col-6 col-md-3">{{ $vehicule->gestionnaire?->name ?? 'Non affecté' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-3 mb-3 row-cols-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Changements de statut</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['changements_statut'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Sorties de pièces</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['sorties_pieces'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Dépannages traités</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['depannages_traites'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($rapportsIntervention->isNotEmpty())
        <div class="card shadow-sm border-0 bg-white mb-3">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h6 text-uppercase text-muted mb-0">Rapports d'intervention</h2>
            </div>
            <div class="card-body pt-2">
                @foreach ($rapportsIntervention as $rapport)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                            <div class="fw-semibold small">{{ $rapport->ancien_statut_libelle ?? 'Création' }} → {{ $rapport->nouveau_statut_libelle }}</div>
                            <div class="text-muted small text-nowrap">{{ $rapport->created_at->format('d/m/Y H:i') }} — {{ $rapport->user?->name ?? '—' }}</div>
                        </div>
                        <div class="small mt-1">{{ $rapport->commentaire }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-rapport" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-rapport">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-rapport">
                        Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white border-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-rapport-statuts" type="button">Changements de statut</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="onglet-rapport-sorties" data-bs-toggle="tab" data-bs-target="#tab-rapport-sorties" type="button">Sorties de pièces</button>
                </li>
            </ul>
        </div>
        <div class="card-body pt-3">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-rapport-statuts">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-rapport-statuts">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transition</th>
                                    <th>Auteur</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-rapport-sorties">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-rapport-sorties">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th class="text-end">Articles</th>
                                    <th class="text-end">Quantité</th>
                                    <th>Motif</th>
                                    <th>Auteur</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            function filtres() {
                return {
                    date_debut: $('#filtres-rapport [name=date_debut]').val(),
                    date_fin: $('#filtres-rapport [name=date_fin]').val(),
                };
            }

            function actualiserBoutonReset() {
                const actif = Object.values(filtres()).some((v) => !!v);
                $('#btn-reset-rapport').toggleClass('d-none', !actif);
            }

            $('#filtres-rapport').on('change input', actualiserBoutonReset);

            const tableStatuts = $('#table-rapport-statuts').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.vehicules.rapport.statuts', $vehicule) }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'transition', name: 'transition', orderable: false },
                    { data: 'auteur', name: 'auteur', orderable: false },
                    { data: 'commentaire', name: 'commentaire', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            const tableSorties = $('#table-rapport-sorties').DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: '{{ route('flotte.vehicules.rapport.sorties', $vehicule) }}', data: (d) => Object.assign(d, filtres()) },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_sortie', name: 'date_sortie' },
                    { data: 'reference', name: 'reference' },
                    { data: 'lignes_count', name: 'lignes_count', className: 'text-end', orderable: false },
                    { data: 'quantite_totale', name: 'quantite_totale', className: 'text-end', orderable: false },
                    { data: 'motif', name: 'motif' },
                    { data: 'auteur', name: 'auteur', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            $('#onglet-rapport-sorties').on('shown.bs.tab', () => tableSorties.columns.adjust());

            $('#btn-filtrer-rapport').on('click', function () {
                tableStatuts.ajax.reload();
                tableSorties.ajax.reload();
            });

            $('#btn-reset-rapport').on('click', function () {
                $('#filtres-rapport')[0].reset();
                tableStatuts.ajax.reload();
                tableSorties.ajax.reload();
            });
        });
        </script>
    @endpush
</x-app-layout>
