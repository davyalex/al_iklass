@php
    $badgesFinancement = [
        'en_cours' => ['bg-warning text-dark', 'En cours'],
        'solde' => ['bg-success', 'Soldé'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Compte prêteur — {{ $preteur->nom }}</x-slot>

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <a href="{{ route('financements.preteurs.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Retour aux prêteurs
        </a>
        <a href="{{ route('financements.preteurs.compte.pdf', $preteur) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimer le relevé complet
        </a>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="small text-muted">Nom</div>
                    <div class="fw-semibold">{{ $preteur->nom }}</div>
                </div>
                <div class="col-sm-2">
                    <div class="small text-muted">Type</div>
                    <div class="fw-semibold">{{ $preteur->type_preteur_libelle }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="small text-muted">Téléphone</div>
                    <div class="fw-semibold">{{ $preteur->telephone ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="small text-muted">Email</div>
                    <div class="fw-semibold">{{ $preteur->email ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3 row-cols-1 row-cols-lg-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total emprunté</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($kpis['total_emprunte']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Déjà remboursé</div>
                    <div class="h5 mb-0 text-success">{{ \App\Support\Money::format($kpis['deja_rembourse']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Reste à rembourser</div>
                    <div class="h5 mb-0 text-danger">{{ \App\Support\Money::format($kpis['reste_a_rembourser']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Financements (emprunts) --}}
    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Financements (emprunts)</h2>
            <x-export-dropdown id-suffix="financements-compte" />
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Remboursé</th>
                        <th class="text-end">Restant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($financements as $financement)
                        <tr>
                            <td>{{ $financement->date_financement->format('d/m/Y') }}</td>
                            <td>{{ $financement->reference ?? '—' }}</td>
                            <td class="text-end">{{ \App\Support\Money::format($financement->montant_total) }} FCFA</td>
                            <td class="text-end">{{ \App\Support\Money::format($financement->montant_rembourse) }} FCFA</td>
                            <td class="text-end">{{ \App\Support\Money::format($financement->montant_restant) }} FCFA</td>
                            <td><span class="badge {{ $badgesFinancement[$financement->statut][0] ?? 'bg-secondary' }}">{{ $badgesFinancement[$financement->statut][1] ?? $financement->statut }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Aucun financement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Historique du compte (financements + remboursements, tout mouvement confondu) --}}
    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Historique du compte</h2>
            <x-export-dropdown id-suffix="remboursements-compte" />
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Libellé</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mouvements as $mouvement)
                        <tr>
                            <td>{{ $mouvement['date']->format('d/m/Y') }}</td>
                            <td>
                                @if ($mouvement['type'] === 'financement')
                                    <span class="badge bg-secondary">Emprunt</span>
                                @else
                                    <span class="badge bg-success">Remboursement</span>
                                @endif
                            </td>
                            <td>{{ $mouvement['libelle'] }}</td>
                            <td class="text-end">{{ \App\Support\Money::format($mouvement['montant']) }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Aucun mouvement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const preteurId = {{ $preteur->id }};

            $('#btn-export-excel-financements-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('financements.financements.export.excel') }}?preteur_id=${preteurId}`;
            });
            $('#btn-export-pdf-financements-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('financements.financements.export.pdf') }}?preteur_id=${preteurId}`;
            });

            $('#btn-export-excel-remboursements-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('financements.remboursements.export.excel') }}?preteur_id=${preteurId}`;
            });
            $('#btn-export-pdf-remboursements-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('financements.remboursements.export.pdf') }}?preteur_id=${preteurId}`;
            });
        });
        </script>
    @endpush
</x-app-layout>
