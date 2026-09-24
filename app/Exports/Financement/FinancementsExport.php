<?php

namespace App\Exports\Financement;

use App\Models\Financement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinancementsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Financement>  $financements
     */
    public function __construct(private readonly Collection $financements) {}

    public function collection(): Collection
    {
        return $this->financements;
    }

    public function headings(): array
    {
        return ['Date', 'Référence', 'Prêteur', 'Total (FCFA)', 'Remboursé (FCFA)', 'Restant (FCFA)', 'Statut'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($financement): array
    {
        return [
            $financement->date_financement->format('d/m/Y'),
            $financement->reference ?? '—',
            $financement->preteur_nom,
            (float) $financement->montant_total,
            (float) $financement->montant_rembourse,
            (float) $financement->montant_restant,
            $financement->statut,
        ];
    }
}
