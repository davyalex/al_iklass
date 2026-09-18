<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1c2b39; }
        .entete { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .entete h1 { font-size: 20px; color: #073763; margin: 0 0 4px; }
        .entete .reference { color: #0257bd; font-weight: bold; }
        .statut { display: inline-block; padding: 3px 10px; border-radius: 4px; color: #fff; font-size: 11px; }
        .bloc { margin-bottom: 16px; }
        .bloc h2 { font-size: 12px; text-transform: uppercase; color: #666; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #e6f2fb; color: #073763; }
        tfoot td { font-weight: bold; background-color: #f5f5f5; }
        .recap { display: flex; justify-content: flex-end; margin-top: 12px; }
        .recap table { width: 300px; }
        .pied { margin-top: 30px; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="entete">
        <div>
            <h1>Achat / Réception</h1>
            <div class="reference">{{ $achat->reference }}</div>
            @if ($achat->bonCommande)
                <div style="font-size: 11px; color: #666;">Bon de commande : {{ $achat->bonCommande->reference }}</div>
            @endif
        </div>
        <div style="text-align: right;">
            <div>Date : {{ $achat->date_achat->format('d/m/Y') }}</div>
            <div class="statut" style="background-color:
                {{ match($achat->statut_paiement) {
                    'comptant' => '#198754',
                    'partiel' => '#ffc107',
                    'credit' => '#dc3545',
                    default => '#6c757d',
                } }};">
                {{ ucfirst($achat->statut_paiement) }}
            </div>
        </div>
    </div>

    <div class="bloc">
        <h2>Fournisseur</h2>
        <div>{{ $achat->fournisseur_nom }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence article</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($achat->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->article_reference }}</td>
                    <td>{{ $ligne->article_nom }}</td>
                    <td>{{ $ligne->quantite }}</td>
                    <td>{{ number_format((float) $ligne->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                    <td>{{ number_format((float) $ligne->montant, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="recap">
        <table>
            <tr><td>Total</td><td>{{ number_format((float) $achat->montant_total, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td>Payé</td><td>{{ number_format((float) $achat->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td>Restant dû</td><td>{{ number_format((float) $achat->montant_restant, 0, ',', ' ') }} FCFA</td></tr>
        </table>
    </div>

    @if ($achat->commentaire)
        <div class="bloc" style="margin-top: 16px;">
            <h2>Commentaire</h2>
            <div>{{ $achat->commentaire }}</div>
        </div>
    @endif

    <div class="pied">
        Édité le {{ now()->format('d/m/Y H:i') }} — AL-IKLASS
    </div>
</body>
</html>
