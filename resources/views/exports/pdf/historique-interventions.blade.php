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
    <h1>Historique des interventions</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $interventions->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Véhicule</th>
                <th>Type de panne</th>
                <th>Description</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Rapport</th>
                <th>Clôturée par</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($interventions as $intervention)
                <tr>
                    <td>{{ $intervention->vehicule_code }}</td>
                    <td>{{ $intervention->type_panne_libelle ?? '—' }}</td>
                    <td>{{ $intervention->description }}</td>
                    <td>{{ $intervention->date_debut->format('d/m/Y') }}</td>
                    <td>{{ $intervention->date_fin?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $intervention->rapport ?? '—' }}</td>
                    <td>{{ $intervention->clotureePar?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
