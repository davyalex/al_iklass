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
    <h1>Sorties de stock</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $sorties->count() }} sortie(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Nature</th>
                <th>Articles</th>
                <th>Quantité totale</th>
                <th>Destination</th>
                <th>Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sorties as $sortie)
                <tr>
                    <td>{{ $sortie->date_sortie->format('d/m/Y') }}</td>
                    <td>{{ $sortie->reference }}</td>
                    <td>{{ $sortie->nature === 'interne' ? 'Interne' : 'Vente externe' }}</td>
                    <td>{{ $sortie->lignes->count() }}</td>
                    <td>{{ $sortie->lignes->sum('quantite') }}</td>
                    <td>
                        {{ $sortie->nature === 'interne'
                            ? ($sortie->vehicule_code ?? '—')
                            : trim(($sortie->vehicule_externe ?? '').' / '.($sortie->acheteur ?? '')) }}
                    </td>
                    <td>{{ number_format((float) $sortie->montant_total, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Total</td>
                <td>{{ number_format((float) $sorties->sum('montant_total'), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
