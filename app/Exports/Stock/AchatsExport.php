<?php

namespace App\Exports\Stock;

use App\Models\Achat;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AchatsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Achat>  $achats
     */
    public function __construct(private readonly Collection $achats) {}

    public function collection(): Collection
    {
        return $this->achats;
    }

    public function headings(): array
    {
        return ['Date', 'Référence', 'Fournisseur', 'Total (FCFA)', 'Payé (FCFA)', 'Restant (FCFA)', 'Statut'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($achat): array
    {
        return [
            $achat->date_achat->format('d/m/Y'),
            $achat->reference ?? '—',
            $achat->fournisseur_nom,
            (float) $achat->montant_total,
            (float) $achat->montant_paye,
            (float) $achat->montant_restant,
            $achat->statut_paiement,
        ];
    }
}
