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
    @include('exports.pdf.partials.entete-societe')
    <h1>Mouvements de stock</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $mouvements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Article</th>
                <th>Type</th>
                <th>Quantité</th>
                <th>Prix unitaire (FCFA)</th>
                <th>Origine</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mouvements as $mouvement)
                <tr>
                    <td>{{ $mouvement->date_mouvement->format('d/m/Y H:i') }}</td>
                    <td>{{ $mouvement->article_reference }} — {{ $mouvement->article_nom }}</td>
                    <td>{{ \App\Http\Controllers\Stock\MouvementStockController::typeLibelle($mouvement) }}</td>
                    <td>{{ $mouvement->quantite }}</td>
                    <td>{{ \App\Support\Money::format((float) $mouvement->prix_unitaire) }}</td>
                    <td>{{ \App\Http\Controllers\Stock\MouvementStockController::origineLibelle($mouvement) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
