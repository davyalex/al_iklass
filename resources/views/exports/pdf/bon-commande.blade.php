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
        .pied { margin-top: 30px; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    @include('exports.pdf.partials.entete-societe')
    <div class="entete">
        <div>
            <h1>Bon de commande</h1>
            <div class="reference">{{ $bonCommande->reference }}</div>
        </div>
        <div style="text-align: right;">
            <div>Date : {{ $bonCommande->date_commande->format('d/m/Y') }}</div>
            <div class="statut" style="background-color:
                {{ match($bonCommande->statut) {
                    'en_attente' => '#6c757d',
                    'partiellement_recu' => '#ffc107',
                    'recu' => '#198754',
                    'annule' => '#dc3545',
                    default => '#6c757d',
                } }};">
                {{ match($bonCommande->statut) {
                    'en_attente' => 'En attente',
                    'partiellement_recu' => 'Partiellement reçu',
                    'recu' => 'Reçu',
                    'annule' => 'Annulé',
                    default => $bonCommande->statut,
                } }}
            </div>
        </div>
    </div>

    <div class="bloc">
        <h2>Fournisseur</h2>
        <div>{{ $bonCommande->fournisseur_nom }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence article</th>
                <th>Désignation</th>
                <th>Qté commandée</th>
                <th>Qté reçue</th>
                <th>Prix unitaire estimé</th>
                <th>Montant estimé</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bonCommande->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->article_reference }}</td>
                    <td>{{ $ligne->article_nom }}</td>
                    <td>{{ $ligne->quantite_commandee }}</td>
                    <td>{{ $ligne->quantite_recue }}</td>
                    <td>{{ number_format((float) $ligne->prix_unitaire_estime, 0, ',', ' ') }} FCFA</td>
                    <td>{{ number_format((float) $ligne->montant_estime, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total estimé</td>
                <td>{{ number_format((float) $bonCommande->lignes->sum('montant_estime'), 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>

    @if ($bonCommande->commentaire)
        <div class="bloc" style="margin-top: 16px;">
            <h2>Commentaire</h2>
            <div>{{ $bonCommande->commentaire }}</div>
        </div>
    @endif

    <div class="pied">
        Édité le {{ now()->format('d/m/Y H:i') }} — {{ app(\App\Services\Admin\IdentiteApplicationService::class)->nom() }}
    </div>
</body>
</html>
