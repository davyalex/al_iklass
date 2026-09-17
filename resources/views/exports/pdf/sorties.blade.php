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
    </style>
</head>
<body>
    <h1>Sorties de stock</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $mouvements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Article</th>
                <th>Nature</th>
                <th>Quantité</th>
                <th>Destination</th>
                <th>Prix de vente (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mouvements as $mouvement)
                <tr>
                    <td>{{ $mouvement->date_mouvement->format('d/m/Y H:i') }}</td>
                    <td>{{ $mouvement->article_nom }}</td>
                    <td>{{ $mouvement->nature === 'interne' ? 'Interne' : 'Vente externe' }}</td>
                    <td>{{ $mouvement->quantite }}</td>
                    <td>
                        {{ $mouvement->nature === 'interne'
                            ? ($mouvement->vehicule_code ?? '—')
                            : trim(($mouvement->vehicule_externe ?? '').' / '.($mouvement->acheteur ?? '')) }}
                    </td>
                    <td>{{ $mouvement->prix_vente !== null ? number_format((float) $mouvement->prix_vente, 0, ',', ' ') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
