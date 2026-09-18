<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1c2b39; }
        .entete { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .entete h1 { font-size: 20px; color: #073763; margin: 0 0 4px; }
        .bloc { margin-bottom: 16px; }
        .bloc h2 { font-size: 12px; text-transform: uppercase; color: #666; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background-color: #e6f2fb; color: #073763; }
        tfoot td { font-weight: bold; background-color: #f5f5f5; }
        .recap { display: flex; gap: 10px; margin-bottom: 20px; }
        .recap .case { flex: 1; border: 1px solid #ccc; border-radius: 4px; padding: 8px; text-align: center; }
        .recap .case .label { font-size: 9px; text-transform: uppercase; color: #666; }
        .recap .case .valeur { font-size: 13px; font-weight: bold; color: #073763; margin-top: 2px; }
        .pied { margin-top: 20px; color: #666; font-size: 10px; }
        h2.section { font-size: 13px; color: #073763; margin: 18px 0 4px; }
    </style>
</head>
<body>
    <div class="entete">
        <div>
            <h1>Relevé de compte fournisseur</h1>
            <div style="font-weight: bold;">{{ $fournisseur->nom }}</div>
            <div style="font-size: 10px; color: #666;">
                {{ $fournisseur->telephone ?? '—' }} · {{ $fournisseur->email ?? '—' }}
            </div>
        </div>
        <div style="text-align: right; font-size: 10px; color: #666;">
            Édité le {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="recap">
        <div class="case">
            <div class="label">Total achats</div>
            <div class="valeur">{{ number_format($kpis['total_achats'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="case">
            <div class="label">Total bons de commande</div>
            <div class="valeur">{{ number_format($kpis['total_bons_commande'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="case">
            <div class="label">Solde dû</div>
            <div class="valeur">{{ number_format($kpis['solde_du'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="case">
            <div class="label">Déjà réglé</div>
            <div class="valeur">{{ number_format($kpis['deja_regle'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="case">
            <div class="label">Reste à régler</div>
            <div class="valeur">{{ number_format($kpis['reste_a_regler'], 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    <h2 class="section">Bons de commande</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Référence</th><th>Statut</th><th>Commandé</th><th>Reçu</th><th>Total estimé (FCFA)</th></tr>
        </thead>
        <tbody>
            @forelse ($bonsCommande as $bc)
                <tr>
                    <td>{{ $bc->date_commande->format('d/m/Y') }}</td>
                    <td>{{ $bc->reference ?? '—' }}</td>
                    <td>{{ $bc->statut }}</td>
                    <td>{{ $bc->lignes->sum('quantite_commandee') }}</td>
                    <td>{{ $bc->lignes->sum('quantite_recue') }}</td>
                    <td>{{ number_format((float) $bc->lignes->sum('montant_estime'), 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucun bon de commande.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section">Achats (réceptions)</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Référence</th><th>Total (FCFA)</th><th>Payé (FCFA)</th><th>Restant (FCFA)</th><th>Statut</th></tr>
        </thead>
        <tbody>
            @forelse ($achats as $achat)
                <tr>
                    <td>{{ $achat->date_achat->format('d/m/Y') }}</td>
                    <td>{{ $achat->reference ?? '—' }}</td>
                    <td>{{ number_format((float) $achat->montant_total, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $achat->montant_paye, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $achat->montant_restant, 0, ',', ' ') }}</td>
                    <td>{{ $achat->statut_paiement }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucun achat.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td>{{ number_format((float) $achats->sum('montant_total'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $achats->sum('montant_paye'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $achats->sum('montant_restant'), 0, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <h2 class="section">Historique du compte</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Type</th><th>Libellé</th><th>Montant (FCFA)</th></tr>
        </thead>
        <tbody>
            @forelse ($mouvements as $mouvement)
                <tr>
                    <td>{{ $mouvement['date']->format('d/m/Y') }}</td>
                    <td>{{ $mouvement['type'] === 'achat' ? 'Achat' : 'Paiement' }}</td>
                    <td>{{ $mouvement['libelle'] }}</td>
                    <td>{{ number_format($mouvement['montant'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Aucun mouvement.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pied">
        AL-IKLASS — Relevé de compte fournisseur
    </div>
</body>
</html>
