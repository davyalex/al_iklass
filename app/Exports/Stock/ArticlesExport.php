<?php

namespace App\Exports\Stock;

use App\Models\Article;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArticlesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Article>  $articles
     */
    public function __construct(private readonly Collection $articles) {}

    public function collection(): Collection
    {
        return $this->articles;
    }

    public function headings(): array
    {
        return ['Référence', 'Nom', 'Catégorie', 'Unité', 'Stock', 'Seuil d\'alerte', 'Prix d\'achat (FCFA)', 'Statut'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($article): array
    {
        return [
            $article->reference,
            $article->nom,
            $article->categorie?->libelle ?? 'Sans catégorie',
            $article->unite?->libelle ?? '—',
            $article->quantite_stock,
            $article->seuil_alerte,
            (float) $article->prix_achat,
            $article->actif ? 'Actif' : 'Inactif',
        ];
    }
}
