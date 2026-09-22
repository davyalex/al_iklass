<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SortieLigne extends Model
{
    use SoftDeletes;

    protected $table = 'sortie_lignes';

    protected $fillable = [
        'sortie_id', 'article_id', 'article_reference', 'article_nom',
        'quantite', 'prix_unitaire', 'prix_vente', 'montant',
    ];

    protected function casts(): array
    {
        return [
            'prix_unitaire' => 'decimal:2',
            'prix_vente' => 'decimal:2',
            'montant' => 'decimal:2',
        ];
    }

    public function sortie(): BelongsTo
    {
        return $this->belongsTo(SortieStock::class, 'sortie_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
