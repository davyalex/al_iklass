<?php

namespace App\Exports\Stock;

use App\Models\BonCommande;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BonsCommandeExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, BonCommande>  $bonsCommande
     */
    public function __construct(private readonly Collection $bonsCommande) {}

    public function collection(): Collection
    {
        return $this->bonsCommande;
    }

    public function headings(): array
    {
        return ['Date', 'Référence', 'Fournisseur', 'Statut', 'Commandé', 'Reçu', 'Total estimé (FCFA)'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($bonCommande): array
    {
        $bonCommande->loadMissing('lignes');

        return [
            $bonCommande->date_commande->format('d/m/Y'),
            $bonCommande->reference ?? '—',
            $bonCommande->fournisseur_nom,
            $bonCommande->statut,
            $bonCommande->lignes->sum('quantite_commandee'),
            $bonCommande->lignes->sum('quantite_recue'),
            (float) $bonCommande->lignes->sum('montant_estime'),
        ];
    }
}
