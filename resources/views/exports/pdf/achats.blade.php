<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #073763; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .meta { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background-color: #e6f2fb; }
        tfoot td { font-weight: bold; background-color: #f5f5f5; }
    </style>
</head>
<body>
    @include('exports.pdf.partials.entete-societe')
    <h1>Achats</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $achats->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Fournisseur</th>
                <th>Total (FCFA)</th>
                <th>Payé (FCFA)</th>
                <th>Restant (FCFA)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($achats as $achat)
                <tr>
                    <td>{{ $achat->date_achat->format('d/m/Y') }}</td>
                    <td>{{ $achat->reference ?? '—' }}</td>
                    <td>{{ $achat->fournisseur_nom }}</td>
                    <td>{{ number_format((float) $achat->montant_total, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $achat->montant_paye, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $achat->montant_restant, 0, ',', ' ') }}</td>
                    <td>{{ $achat->statut_paiement }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{ number_format((float) $achats->sum('montant_total'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $achats->sum('montant_paye'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $achats->sum('montant_restant'), 0, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
