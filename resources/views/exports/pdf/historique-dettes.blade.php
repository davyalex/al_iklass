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
    <h1>Historique de la dette</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $historiques->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Gestionnaire</th>
                <th>Type</th>
                <th>Montant (FCFA)</th>
                <th>Dette avant</th>
                <th>Dette après</th>
                <th>Motif</th>
                <th>Auteur</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($historiques as $historique)
                <tr>
                    <td>{{ $historique->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $historique->gestionnaire_nom }}</td>
                    <td>
                        {{ match ($historique->type) {
                            'bascule' => 'Bascule',
                            'reglement' => 'Règlement',
                            default => 'Annulation',
                        } }}
                    </td>
                    <td>{{ number_format((float) $historique->montant, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $historique->dette_avant, 0, ',', ' ') }}</td>
                    <td>{{ number_format((float) $historique->dette_apres, 0, ',', ' ') }}</td>
                    <td>{{ $historique->motif ?? ($historique->date_reference ? 'Reste à verser du '.$historique->date_reference->format('d/m/Y') : '—') }}</td>
                    <td>{{ $historique->user?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
