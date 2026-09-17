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
    <h1>Paiements fournisseurs</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $paiements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Fournisseur</th>
                <th>Montant (FCFA)</th>
                <th>Mode de paiement</th>
                <th>Référence</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($paiements as $paiement)
                <tr>
                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                    <td>{{ $paiement->fournisseur_nom }}</td>
                    <td>{{ number_format((float) $paiement->montant, 0, ',', ' ') }}</td>
                    <td>{{ $paiement->modePaiement->libelle }}</td>
                    <td>{{ $paiement->reference ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td>{{ number_format((float) $paiements->sum('montant'), 0, ',', ' ') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
