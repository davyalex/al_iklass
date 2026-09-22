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
        .recap { display: flex; justify-content: flex-end; margin-top: 12px; }
        .recap table { width: 300px; }
        .pied { margin-top: 30px; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="entete">
        <div>
            <h1>Sortie de stock</h1>
            <div class="reference">{{ $sortie->reference }}</div>
        </div>
        <div style="text-align: right;">
            <div>Date : {{ $sortie->date_sortie->format('d/m/Y') }}</div>
            <div class="statut" style="background-color: {{ $sortie->nature === 'interne' ? '#0d6efd' : '#0dcaf0' }};">
                {{ $sortie->nature === 'interne' ? 'Interne' : 'Vente externe' }}
            </div>
        </div>
    </div>

    <div class="bloc">
        <h2>Destination</h2>
        <div>
            @if ($sortie->nature === 'interne')
                Véhicule : {{ $sortie->vehicule_code ?? '—' }}
            @else
                {{ $sortie->vehicule_externe ?? '—' }} — {{ $sortie->acheteur ?? '—' }}
            @endif
        </div>
        <h2 style="margin-top: 10px;">Motif</h2>
        <div>{{ $sortie->motif ?? '—' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence article</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>{{ $sortie->nature === 'externe' ? 'Prix de vente' : 'Valorisation unitaire' }}</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sortie->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->article_reference }}</td>
                    <td>{{ $ligne->article_nom }}</td>
                    <td>{{ $ligne->quantite }}</td>
                    <td>{{ number_format((float) ($ligne->prix_vente ?? $ligne->prix_unitaire), 0, ',', ' ') }} FCFA</td>
                    <td>{{ number_format((float) $ligne->montant, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="recap">
        <table>
            <tr><td>Montant total</td><td>{{ number_format((float) $sortie->montant_total, 0, ',', ' ') }} FCFA</td></tr>
        </table>
    </div>

    <div class="pied">
        Édité le {{ now()->format('d/m/Y H:i') }} — AL-IKLASS
    </div>
</body>
</html>
