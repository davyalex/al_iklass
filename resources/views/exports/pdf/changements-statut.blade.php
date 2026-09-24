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
    <h1>Changements de statut — {{ $vehicule->code }}</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $changements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Transition</th>
                <th>Auteur</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($changements as $changement)
                <tr>
                    <td>{{ $changement->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $changement->ancien_statut_libelle ?? 'Création' }} → {{ $changement->nouveau_statut_libelle }}</td>
                    <td>{{ $changement->user?->name ?? '—' }}</td>
                    <td>{{ $changement->commentaire ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
