<?php

namespace App\Exports\Admin;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Activitylog\Models\Activity;

class AuditLogExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Activity>  $activites
     */
    public function __construct(private readonly Collection $activites) {}

    public function collection(): Collection
    {
        return $this->activites;
    }

    public function headings(): array
    {
        return ['Date', 'Utilisateur', 'Action'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($activite): array
    {
        return [
            $activite->created_at->format('d/m/Y H:i'),
            $activite->causer?->name ?? 'Système',
            $activite->description,
        ];
    }
}
