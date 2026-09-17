<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'nom', 'description', 'categorie_id', 'unite',
        'quantite_stock', 'prix_achat', 'prix_vente', 'seuil_alerte', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix_achat' => 'decimal:2',
            'prix_vente' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieArticle::class, 'categorie_id');
    }

    public function achatLignes(): HasMany
    {
        return $this->hasMany(AchatLigne::class);
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeEnAlerte(Builder $query): Builder
    {
        return $query->whereColumn('quantite_stock', '<=', 'seuil_alerte');
    }
}
