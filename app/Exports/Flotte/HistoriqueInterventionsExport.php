<?php

namespace App\Exports\Flotte;

use App\Models\Intervention;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoriqueInterventionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Intervention>  $interventions
     */
    public function __construct(private readonly Collection $interventions) {}

    public function collection(): Collection
    {
        return $this->interventions;
    }

    public function headings(): array
    {
        return ['Véhicule', 'Type de panne', 'Description', 'Début', 'Fin', 'Rapport', 'Clôturée par'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($intervention): array
    {
        return [
            $intervention->vehicule_code,
            $intervention->type_panne_libelle ?? '—',
            $intervention->description,
            $intervention->date_debut->format('d/m/Y'),
            $intervention->date_fin?->format('d/m/Y') ?? '—',
            $intervention->rapport ?? '—',
            $intervention->clotureePar?->name ?? '—',
        ];
    }
}
