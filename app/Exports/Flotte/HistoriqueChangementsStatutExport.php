<?php

namespace App\Exports\Flotte;

use App\Models\HistoriqueStatutVehicule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoriqueChangementsStatutExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, HistoriqueStatutVehicule>  $changements
     */
    public function __construct(private readonly Collection $changements) {}

    public function collection(): Collection
    {
        return $this->changements;
    }

    public function headings(): array
    {
        return ['Date', 'Transition', 'Auteur', 'Commentaire'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($changement): array
    {
        return [
            $changement->created_at->format('d/m/Y H:i'),
            ($changement->ancien_statut_libelle ?? 'Création').' → '.$changement->nouveau_statut_libelle,
            $changement->user?->name ?? '—',
            $changement->commentaire ?? '—',
        ];
    }
}
