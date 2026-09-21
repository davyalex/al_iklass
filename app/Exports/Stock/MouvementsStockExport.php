<?php

namespace App\Exports\Stock;

use App\Http\Controllers\Stock\MouvementStockController;
use App\Models\MouvementStock;
use App\Support\Money;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MouvementsStockExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return ['Date', 'Article', 'Type', 'Quantité', 'Prix unitaire (FCFA)', 'Origine'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($mouvement): array
    {
        return [
            $mouvement->date_mouvement->format('d/m/Y H:i'),
            $mouvement->article_reference.' — '.$mouvement->article_nom,
            MouvementStockController::typeLibelle($mouvement),
            $mouvement->quantite,
            Money::format((float) $mouvement->prix_unitaire),
            MouvementStockController::origineLibelle($mouvement),
        ];
    }
}
