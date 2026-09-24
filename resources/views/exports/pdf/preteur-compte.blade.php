<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1c2b39; }
        .entete { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .entete h1 { font-size: 20px; color: #073763; margin: 0 0 4px; }
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
    @include('exports.pdf.partials.entete-societe')
    <div class="entete">
        <div>
            <h1>Relevé de compte prêteur</h1>
            <div style="font-weight: bold;">{{ $preteur->nom }} ({{ $preteur->type_preteur_libelle }})</div>
            <div style="font-size: 10px; color: #666;">
                {{ $preteur->telephone ?? '—' }} · {{ $preteur->email ?? '—' }}
            </div>
        </div>
        <div style="text-align: right; font-size: 10px; color: #666;">
            Édité le {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="recap">
        <div class="case">
            <div class="label">Total emprunté</div>
            <div class="valeur">{{ number_format($kpis['total_emprunte'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="case">
            <div class="label">Déjà remboursé</div>
            <div class="valeur">{{ number_format($kpis['deja_rembourse'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="case">
            <div class="label">Reste à rembourser</div>
            <div class="valeur">{{ number_format($kpis['reste_a_rembourser'], 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    <h2 class="section">Financements (emprunts)</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Référence</th><th>Total (FCFA)</th><th>Remboursé (FCFA)</th><th>Restant (FCFA)</th><th>Statut</th></tr>
        </thead>
        <tbody>
            @forelse ($financements as $financement)
                <tr>
                    <td>{{ $financement->date_financement->format('d/m/Y') }}</td>
                    <td>{{ $financement->reference ?? '—' }}</td>
                    <td>{{ number_format((float) $financement->montant_total, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $financement->montant_rembourse, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $financement->montant_restant, 0, ',', ' ') }}</td>
                    <td>{{ $financement->statut }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucun financement.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td>{{ number_format((float) $financements->sum('montant_total'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $financements->sum('montant_rembourse'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $financements->sum('montant_restant'), 0, ',', ' ') }}</td>
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
                    <td>{{ $mouvement['type'] === 'financement' ? 'Emprunt' : 'Remboursement' }}</td>
                    <td>{{ $mouvement['libelle'] }}</td>
                    <td>{{ number_format($mouvement['montant'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Aucun mouvement.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pied">
        {{ app(\App\Services\Admin\IdentiteApplicationService::class)->nom() }} — Relevé de compte prêteur
    </div>
</body>
</html>
