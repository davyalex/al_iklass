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
    <h1>Inventaires</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $inventaires->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Lignes comptées</th>
                <th>Écarts</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventaires as $inventaire)
                @php
                    $comptees = $inventaire->lignes->whereNotNull('quantite_comptee');
                @endphp
                <tr>
                    <td>{{ $inventaire->date_inventaire->format('d/m/Y') }}</td>
                    <td>{{ $inventaire->reference ?? '—' }}</td>
                    <td>{{ $inventaire->categorie?->libelle ?? 'Tous les articles' }}</td>
                    <td>{{ $inventaire->statut }}</td>
                    <td>{{ $comptees->count() }} / {{ $inventaire->lignes->count() }}</td>
                    <td>{{ $comptees->filter(fn ($l) => $l->ecart() !== 0)->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
