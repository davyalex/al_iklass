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
    <h1>Bons de commande</h1>
    <div class="meta">Édité le {{ now()->format('d/m/Y H:i') }} — {{ $bonsCommande->count() }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Fournisseur</th>
                <th>Statut</th>
                <th>Commandé</th>
                <th>Reçu</th>
                <th>Total estimé (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bonsCommande as $bonCommande)
                <tr>
                    <td>{{ $bonCommande->date_commande->format('d/m/Y') }}</td>
                    <td>{{ $bonCommande->reference ?? '—' }}</td>
                    <td>{{ $bonCommande->fournisseur_nom }}</td>
                    <td>{{ $bonCommande->statut }}</td>
                    <td>{{ $bonCommande->lignes->sum('quantite_commandee') }}</td>
                    <td>{{ $bonCommande->lignes->sum('quantite_recue') }}</td>
                    <td>{{ number_format((float) $bonCommande->lignes->sum('montant_estime'), 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Total estimé</td>
                <td>{{ number_format((float) $bonsCommande->flatMap->lignes->sum('montant_estime'), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
