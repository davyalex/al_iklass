<?php

namespace App\Exports\Stock;

use App\Models\SortieStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SortiesStockExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, SortieStock>  $sorties
     */
    public function __construct(private readonly Collection $sorties) {}

    public function collection(): Collection
    {
        return $this->sorties;
    }

    public function headings(): array
    {
        return ['Date', 'Référence', 'Nature', 'Articles', 'Quantité totale', 'Destination', 'Montant (FCFA)'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($sortie): array
    {
        $destination = $sortie->nature === 'interne'
            ? ($sortie->vehicule_code ?? '—')
            : trim(($sortie->vehicule_externe ?? '').' / '.($sortie->acheteur ?? ''));

        return [
            $sortie->date_sortie->format('d/m/Y'),
            $sortie->reference,
            $sortie->nature === 'interne' ? 'Interne' : 'Vente externe',
            $sortie->lignes->count(),
            $sortie->lignes->sum('quantite'),
            $destination,
            (float) $sortie->montant_total,
        ];
    }
}
