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
    <h1>Caisses — mouvements</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $mouvements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Caisse</th>
                <th>Sens</th>
                <th>Montant (FCFA)</th>
                <th>Mode de paiement</th>
                <th>Référence</th>
                <th>Motif</th>
                <th>Enregistré par</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mouvements as $mouvement)
                <tr>
                    <td>{{ $mouvement->date_mouvement->format('d/m/Y H:i') }}</td>
                    <td>{{ $mouvement->caisse->libelle }}</td>
                    <td>{{ $mouvement->sens === 'entree' ? 'Entrée' : 'Sortie' }}</td>
                    <td>{{ \App\Support\Money::format((float) $mouvement->montant) }}</td>
                    <td>{{ $mouvement->modePaiement?->libelle ?? '—' }}</td>
                    <td>{{ $mouvement->reference ?? '—' }}</td>
                    <td>{{ $mouvement->motif ?? '—' }}</td>
                    <td>{{ $mouvement->user?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
