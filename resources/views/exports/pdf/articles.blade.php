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
        .alerte { color: #b02a37; font-weight: bold; }
    </style>
</head>
<body>
    @include('exports.pdf.partials.entete-societe')
    <h1>Articles</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $articles->count() }} article(s)</div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Unité</th>
                <th>Stock</th>
                <th>Seuil d'alerte</th>
                <th>Prix d'achat (FCFA)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($articles as $article)
                @php $enAlerte = $article->quantite_stock <= $article->seuil_alerte; @endphp
                <tr class="{{ $enAlerte ? 'alerte' : '' }}">
                    <td>{{ $article->reference }}</td>
                    <td>{{ $article->nom }}</td>
                    <td>{{ $article->categorie?->libelle ?? 'Sans catégorie' }}</td>
                    <td>{{ $article->unite?->libelle ?? '—' }}</td>
                    <td>{{ $article->quantite_stock }}{{ $enAlerte ? ' (alerte)' : '' }}</td>
                    <td>{{ $article->seuil_alerte }}</td>
                    <td>{{ number_format((float) $article->prix_achat, 0, ',', ' ') }}</td>
                    <td>{{ $article->actif ? 'Actif' : 'Inactif' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
