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
    <h1>Remboursements</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $remboursements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Prêteur</th>
                <th>Montant (FCFA)</th>
                <th>Mode de paiement</th>
                <th>Référence</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($remboursements as $remboursement)
                <tr>
                    <td>{{ $remboursement->date_remboursement->format('d/m/Y') }}</td>
                    <td>{{ $remboursement->preteur_nom }}</td>
                    <td>{{ number_format((float) $remboursement->montant, 0, ',', ' ') }}</td>
                    <td>{{ $remboursement->modePaiement?->libelle ?? '—' }}</td>
                    <td>{{ $remboursement->reference ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
