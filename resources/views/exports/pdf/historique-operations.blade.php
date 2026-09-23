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
    <h1>Historique des opérations programmées</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $operations->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date de réalisation</th>
                <th>Véhicule</th>
                <th>Type</th>
                <th>Échéance prévue</th>
                <th>Réalisé par</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($operations as $operation)
                <tr>
                    <td>{{ $operation->date_realisation->format('d/m/Y') }}</td>
                    <td>{{ $operation->vehicule_code }}</td>
                    <td>{{ $operation->type_operation_libelle }}</td>
                    <td>{{ $operation->date_echeance->format('d/m/Y') }}</td>
                    <td>{{ $operation->realisePar?->name ?? '—' }}</td>
                    <td>{{ $operation->commentaire ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
