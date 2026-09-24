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
    <h1>Financements</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $financements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Prêteur</th>
                <th>Total (FCFA)</th>
                <th>Remboursé (FCFA)</th>
                <th>Restant (FCFA)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($financements as $financement)
                <tr>
                    <td>{{ $financement->date_financement->format('d/m/Y') }}</td>
                    <td>{{ $financement->reference ?? '—' }}</td>
                    <td>{{ $financement->preteur_nom }}</td>
                    <td>{{ number_format((float) $financement->montant_total, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $financement->montant_rembourse, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $financement->montant_restant, 0, ',', ' ') }}</td>
                    <td>{{ $financement->statut }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{ number_format((float) $financements->sum('montant_total'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $financements->sum('montant_rembourse'), 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $financements->sum('montant_restant'), 0, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
