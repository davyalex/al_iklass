<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Versement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gestionnaire_id', 'gestionnaire_nom', 'vehicule_id', 'vehicule_code', 'montant', 'mode_paiement_id',
        'date_versement', 'reference', 'user_id', 'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_versement' => 'date',
        ];
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionnaire_id');
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
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
