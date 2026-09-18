@php
    $badgesBc = [
        'en_attente' => ['bg-secondary', 'En attente'],
        'partiellement_recu' => ['bg-warning text-dark', 'Partiellement reçu'],
        'recu' => ['bg-success', 'Reçu'],
        'annule' => ['bg-danger', 'Annulé'],
    ];
    $badgesAchat = [
        'comptant' => ['bg-success', 'Comptant'],
        'partiel' => ['bg-warning text-dark', 'Partiel'],
        'credit' => ['bg-danger', 'Crédit'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Compte fournisseur — {{ $fournisseur->nom }}</x-slot>

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <a href="{{ route('stock.fournisseurs.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Retour aux fournisseurs
        </a>
        <a href="{{ route('stock.fournisseurs.compte.pdf', $fournisseur) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimer le relevé complet
        </a>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="small text-muted">Nom</div>
                    <div class="fw-semibold">{{ $fournisseur->nom }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="small text-muted">Téléphone</div>
                    <div class="fw-semibold">{{ $fournisseur->telephone ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="small text-muted">Email</div>
                    <div class="fw-semibold">{{ $fournisseur->email ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3 row-cols-2 row-cols-lg-5">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total achats</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($kpis['total_achats']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total bons de commande</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($kpis['total_bons_commande']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Solde dû</div>
                    <div class="h5 mb-0 text-danger">{{ \App\Support\Money::format($kpis['solde_du']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Déjà réglé</div>
                    <div class="h5 mb-0 text-success">{{ \App\Support\Money::format($kpis['deja_regle']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Reste à régler</div>
                    <div class="h5 mb-0 text-danger">{{ \App\Support\Money::format($kpis['reste_a_regler']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bons de commande --}}
    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Bons de commande</h2>
            <x-export-dropdown id-suffix="bc-compte" />
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Statut</th>
                        <th class="text-end">Commandé</th>
                        <th class="text-end">Reçu</th>
                        <th class="text-end">Total estimé</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bonsCommande as $bc)
                        <tr>
                            <td>{{ $bc->date_commande->format('d/m/Y') }}</td>
                            <td>{{ $bc->reference ?? '—' }}</td>
                            <td><span class="badge {{ $badgesBc[$bc->statut][0] ?? 'bg-secondary' }}">{{ $badgesBc[$bc->statut][1] ?? $bc->statut }}</span></td>
                            <td class="text-end">{{ $bc->lignes->sum('quantite_commandee') }}</td>
                            <td class="text-end">{{ $bc->lignes->sum('quantite_recue') }}</td>
                            <td class="text-end">{{ \App\Support\Money::format($bc->lignes->sum('montant_estime')) }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Aucun bon de commande.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Achats --}}
    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Achats (réceptions)</h2>
            <x-export-dropdown id-suffix="achats-compte" />
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Payé</th>
                        <th class="text-end">Restant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($achats as $achat)
                        <tr>
                            <td>{{ $achat->date_achat->format('d/m/Y') }}</td>
                            <td>{{ $achat->reference ?? '—' }}</td>
                            <td class="text-end">{{ \App\Support\Money::format($achat->montant_total) }} FCFA</td>
                            <td class="text-end">{{ \App\Support\Money::format($achat->montant_paye) }} FCFA</td>
                            <td class="text-end">{{ \App\Support\Money::format($achat->montant_restant) }} FCFA</td>
                            <td><span class="badge {{ $badgesAchat[$achat->statut_paiement][0] ?? 'bg-secondary' }}">{{ $badgesAchat[$achat->statut_paiement][1] ?? $achat->statut_paiement }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Aucun achat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Historique du compte (achats + paiements, tout mouvement confondu) --}}
    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Historique du compte</h2>
            <x-export-dropdown id-suffix="paiements-compte" />
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
                                @if ($mouvement['type'] === 'achat')
                                    <span class="badge bg-secondary">Achat</span>
                                @else
                                    <span class="badge bg-success">Paiement</span>
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
            const fournisseurId = {{ $fournisseur->id }};

            $('#btn-export-excel-bc-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('stock.bons-commande.export.excel') }}?fournisseur_id=${fournisseurId}`;
            });
            $('#btn-export-pdf-bc-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('stock.bons-commande.export.pdf') }}?fournisseur_id=${fournisseurId}`;
            });

            $('#btn-export-excel-achats-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('stock.achats.export.excel') }}?fournisseur_id=${fournisseurId}`;
            });
            $('#btn-export-pdf-achats-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('stock.achats.export.pdf') }}?fournisseur_id=${fournisseurId}`;
            });

            $('#btn-export-excel-paiements-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('stock.paiements.export.excel') }}?fournisseur_id=${fournisseurId}`;
            });
            $('#btn-export-pdf-paiements-compte').on('click', function (e) {
                e.preventDefault();
                window.location = `{{ route('stock.paiements.export.pdf') }}?fournisseur_id=${fournisseurId}`;
            });
        });
        </script>
    @endpush
</x-app-layout>
