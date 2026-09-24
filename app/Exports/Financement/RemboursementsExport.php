<?php

namespace App\Exports\Financement;

use App\Models\RemboursementFinancement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RemboursementsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, RemboursementFinancement>  $remboursements
     */
    public function __construct(private readonly Collection $remboursements) {}

    public function collection(): Collection
    {
        return $this->remboursements;
    }

    public function headings(): array
    {
        return ['Date', 'Prêteur', 'Montant (FCFA)', 'Mode de paiement', 'Référence'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($remboursement): array
    {
        return [
            $remboursement->date_remboursement->format('d/m/Y'),
            $remboursement->preteur_nom,
            (float) $remboursement->montant,
            $remboursement->modePaiement?->libelle ?? '—',
            $remboursement->reference ?? '—',
        ];
    }
}
