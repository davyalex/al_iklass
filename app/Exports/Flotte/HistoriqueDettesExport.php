<?php

namespace App\Exports\Flotte;

use App\Models\HistoriqueDette;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoriqueDettesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, HistoriqueDette>  $historiques
     */
    public function __construct(private readonly Collection $historiques) {}

    public function collection(): Collection
    {
        return $this->historiques;
    }

    public function headings(): array
    {
        return ['Date', 'Gestionnaire', 'Type', 'Montant (FCFA)', 'Dette avant', 'Dette après', 'Motif', 'Auteur'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($historique): array
    {
        return [
            $historique->created_at->format('d/m/Y H:i'),
            $historique->gestionnaire_nom,
            match ($historique->type) {
                'bascule' => 'Bascule',
                'reglement' => 'Règlement',
                default => 'Annulation',
            },
            (float) $historique->montant,
            (float) $historique->dette_avant,
            (float) $historique->dette_apres,
            $historique->motif ?? ($historique->date_reference ? 'Reste à verser du '.$historique->date_reference->format('d/m/Y') : '—'),
            $historique->user?->name ?? '—',
        ];
    }
}
