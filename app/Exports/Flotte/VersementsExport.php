<?php

namespace App\Exports\Flotte;

use App\Models\Versement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VersementsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Versement>  $versements
     */
    public function __construct(private readonly Collection $versements) {}

    public function collection(): Collection
    {
        return $this->versements;
    }

    public function headings(): array
    {
        return ['Date', 'Gestionnaire', 'Véhicule', 'Montant (FCFA)', 'Mode de paiement', 'Référence', 'Enregistré par'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($versement): array
    {
        return [
            $versement->date_versement->format('d/m/Y'),
            $versement->gestionnaire_nom,
            $versement->vehicule_code ?? '—',
            (float) $versement->montant,
            $versement->modePaiement->libelle,
            $versement->reference ?? '—',
            $versement->user?->name ?? '—',
        ];
    }
}
