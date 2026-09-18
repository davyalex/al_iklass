<?php

namespace App\Exports\Stock;

use App\Models\Inventaire;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventairesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Inventaire>  $inventaires
     */
    public function __construct(private readonly Collection $inventaires) {}

    public function collection(): Collection
    {
        return $this->inventaires;
    }

    public function headings(): array
    {
        return ['Date', 'Référence', 'Catégorie', 'Statut', 'Lignes comptées', 'Écarts'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($inventaire): array
    {
        $inventaire->loadMissing('lignes', 'categorie');

        $comptees = $inventaire->lignes->whereNotNull('quantite_comptee');

        return [
            $inventaire->date_inventaire->format('d/m/Y'),
            $inventaire->reference ?? '—',
            $inventaire->categorie?->libelle ?? 'Tous les articles',
            $inventaire->statut,
            $comptees->count().' / '.$inventaire->lignes->count(),
            $comptees->filter(fn ($l) => $l->ecart() !== 0)->count(),
        ];
    }
}
