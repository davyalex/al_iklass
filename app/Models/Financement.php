<?php

namespace App\Models;

use Database\Factories\FinancementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Financement extends Model
{
    /** @use HasFactory<FinancementFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'preteur_id', 'preteur_nom', 'date_financement',
        'montant_total', 'montant_rembourse', 'montant_restant', 'statut',
        'commentaire', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_financement' => 'date',
            'montant_total' => 'decimal:2',
            'montant_rembourse' => 'decimal:2',
            'montant_restant' => 'decimal:2',
        ];
    }

    public function preteur(): BelongsTo
    {
        return $this->belongsTo(Preteur::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(RemboursementFinancement::class);
    }

    public function scopeDuMois(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $date ??= now();

        return $query->whereYear('date_financement', $date->format('Y'))
            ->whereMonth('date_financement', $date->format('m'));
    }

    public static function deriverStatut(float $montantRestant): string
    {
        return $montantRestant <= 0 ? 'solde' : 'en_cours';
    }
}
