<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MouvementStock extends Model
{
    use SoftDeletes;

    protected $table = 'mouvements_stock';

    protected $fillable = [
        'article_id', 'article_reference', 'article_nom', 'type', 'nature',
        'quantite', 'prix_unitaire', 'prix_vente', 'motif',
        'vehicule_id', 'vehicule_code', 'vehicule_externe', 'acheteur',
        'achat_id', 'sortie_id', 'inventaire_id', 'intervention_id', 'caisse_mouvement_id', 'user_id', 'date_mouvement',
    ];

    protected function casts(): array
    {
        return [
            'prix_unitaire' => 'decimal:2',
            'prix_vente' => 'decimal:2',
            'date_mouvement' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    public function sortie(): BelongsTo
    {
        return $this->belongsTo(SortieStock::class, 'sortie_id');
    }

    public function mouvementCaisse(): BelongsTo
    {
        return $this->belongsTo(MouvementCaisse::class, 'caisse_mouvement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEntrees(Builder $query): Builder
    {
        return $query->where('type', 'entree');
    }

    public function scopeSorties(Builder $query): Builder
    {
        return $query->where('type', 'sortie');
    }

    public function scopeInterne(Builder $query): Builder
    {
        return $query->where('nature', 'interne');
    }

    public function scopeExterne(Builder $query): Builder
    {
        return $query->where('nature', 'externe');
    }

    public function scopeAjustement(Builder $query): Builder
    {
        return $query->where('nature', 'ajustement');
    }

    public function scopeDuMois(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $date ??= now();

        return $query->whereYear('date_mouvement', $date->format('Y'))
            ->whereMonth('date_mouvement', $date->format('m'));
    }
}
