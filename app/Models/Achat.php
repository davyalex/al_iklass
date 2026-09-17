<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Achat extends Model
{
    /** @use HasFactory<\Database\Factories\AchatFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'fournisseur_id', 'fournisseur_nom', 'date_achat',
        'montant_total', 'montant_paye', 'montant_restant', 'statut_paiement',
        'commentaire', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_achat' => 'date',
            'montant_total' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'montant_restant' => 'decimal:2',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(AchatLigne::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementFournisseur::class);
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function scopeDuMois(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $date ??= now();

        return $query->whereYear('date_achat', $date->format('Y'))
            ->whereMonth('date_achat', $date->format('m'));
    }
}
