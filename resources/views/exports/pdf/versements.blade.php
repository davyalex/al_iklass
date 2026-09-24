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
        tfoot td { font-weight: bold; background-color: #f5f5f5; }
    </style>
</head>
<body>
    @include('exports.pdf.partials.entete-societe')
    <h1>Versements gestionnaires</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $versements->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Gestionnaire</th>
                <th>Véhicule</th>
                <th>Montant (FCFA)</th>
                <th>Mode de paiement</th>
                <th>Référence</th>
                <th>Enregistré par</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($versements as $versement)
                <tr>
                    <td>{{ $versement->date_versement->format('d/m/Y') }}</td>
                    <td>{{ $versement->gestionnaire_nom }}</td>
                    <td>{{ $versement->vehicule_code ?? '—' }}</td>
                    <td>{{ number_format((float) $versement->montant, 0, ',', ' ') }}</td>
                    <td>{{ $versement->modePaiement->libelle }}</td>
                    <td>{{ $versement->reference ?? '—' }}</td>
                    <td>{{ $versement->user?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{ number_format((float) $versements->sum('montant'), 0, ',', ' ') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
