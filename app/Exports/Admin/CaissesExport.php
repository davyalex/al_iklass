<?php

namespace App\Exports\Admin;

use App\Models\MouvementCaisse;
use App\Support\Money;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CaissesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, MouvementCaisse>  $mouvements
     */
    public function __construct(private readonly Collection $mouvements) {}

    public function collection(): Collection
    {
        return $this->mouvements;
    }

    public function headings(): array
    {
        return ['Date', 'Caisse', 'Sens', 'Montant (FCFA)', 'Mode de paiement', 'Référence', 'Motif', 'Enregistré par'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($mouvement): array
    {
        return [
            $mouvement->date_mouvement->format('d/m/Y H:i'),
            $mouvement->caisse->libelle,
            $mouvement->sens === 'entree' ? 'Entrée' : 'Sortie',
            Money::format((float) $mouvement->montant),
            $mouvement->modePaiement?->libelle ?? '—',
            $mouvement->reference ?? '—',
            $mouvement->motif ?? '—',
            $mouvement->user?->name ?? '—',
        ];
    }
}
