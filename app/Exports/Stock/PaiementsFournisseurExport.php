<?php

namespace App\Exports\Stock;

use App\Models\PaiementFournisseur;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaiementsFournisseurExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, PaiementFournisseur>  $paiements
     */
    public function __construct(private readonly Collection $paiements) {}

    public function collection(): Collection
    {
        return $this->paiements;
    }

    public function headings(): array
    {
        return ['Date', 'Fournisseur', 'Montant (FCFA)', 'Mode de paiement', 'Référence'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($paiement): array
    {
        return [
            $paiement->date_paiement->format('d/m/Y'),
            $paiement->fournisseur_nom,
            (float) $paiement->montant,
            $paiement->modePaiement->libelle,
            $paiement->reference ?? '—',
        ];
    }
}
