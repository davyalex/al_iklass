<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SortieStock extends Model
{
    use SoftDeletes;

    protected $table = 'sorties_stock';

    protected $fillable = [
        'reference', 'nature', 'date_sortie', 'motif',
        'vehicule_id', 'vehicule_code', 'vehicule_externe', 'acheteur',
        'montant_total', 'caisse_mouvement_id', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_sortie' => 'date',
            'montant_total' => 'decimal:2',
        ];
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(SortieLigne::class, 'sortie_id');
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class, 'sortie_id');
    }

    public function mouvementCaisse(): BelongsTo
    {
        return $this->belongsTo(MouvementCaisse::class, 'caisse_mouvement_id');
    }

    public function scopeInterne(Builder $query): Builder
    {
        return $query->where('nature', 'interne');
    }

    public function scopeExterne(Builder $query): Builder
    {
        return $query->where('nature', 'externe');
    }

    public function scopeDuMois(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $date ??= now();

        return $query->whereYear('date_sortie', $date->format('Y'))
            ->whereMonth('date_sortie', $date->format('m'));
    }
}
