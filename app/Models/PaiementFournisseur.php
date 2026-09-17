<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaiementFournisseur extends Model
{
    use SoftDeletes;

    protected $table = 'paiements_fournisseur';

    protected $fillable = [
        'achat_id', 'date_paiement', 'montant', 'mode_paiement_id',
        'reference', 'fournisseur_nom', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_paiement' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function modePaiement(): BelongsTo
    {
        return $this->belongsTo(ModePaiement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
