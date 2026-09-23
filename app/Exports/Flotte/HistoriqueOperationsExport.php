<?php

namespace App\Exports\Flotte;

use App\Models\OperationProgrammee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoriqueOperationsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, OperationProgrammee>  $operations
     */
    public function __construct(private readonly Collection $operations) {}

    public function collection(): Collection
    {
        return $this->operations;
    }

    public function headings(): array
    {
        return ['Date de réalisation', 'Véhicule', 'Type', 'Échéance prévue', 'Réalisé par', 'Commentaire'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($operation): array
    {
        return [
            $operation->date_realisation->format('d/m/Y'),
            $operation->vehicule_code,
            $operation->type_operation_libelle,
            $operation->date_echeance->format('d/m/Y'),
            $operation->realisePar?->name ?? '—',
            $operation->commentaire ?? '—',
        ];
    }
}
