<?php

namespace App\Exports\Stock;

use App\Models\MouvementStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SortiesStockExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, MouvementStock>  $mouvements
     */
    public function __construct(private readonly Collection $mouvements) {}

    public function collection(): Collection
    {
        return $this->mouvements;
    }

    public function headings(): array
    {
        return ['Date', 'Article', 'Nature', 'Quantité', 'Destination', 'Prix de vente (FCFA)'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($mouvement): array
    {
        $destination = $mouvement->nature === 'interne'
            ? ($mouvement->vehicule_code ?? '—')
            : trim(($mouvement->vehicule_externe ?? '').' / '.($mouvement->acheteur ?? ''));

        return [
            $mouvement->date_mouvement->format('d/m/Y H:i'),
            $mouvement->article_nom,
            $mouvement->nature === 'interne' ? 'Interne' : 'Vente externe',
            $mouvement->quantite,
            $destination,
            $mouvement->prix_vente !== null ? (float) $mouvement->prix_vente : '—',
        ];
    }
}
