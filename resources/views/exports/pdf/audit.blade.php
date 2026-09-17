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
    <h1>Journal d'audit</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $activites->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($activites as $activite)
                <tr>
                    <td>{{ $activite->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $activite->causer?->name ?? 'Système' }}</td>
                    <td>{{ $activite->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
