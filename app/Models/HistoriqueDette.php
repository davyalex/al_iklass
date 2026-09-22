<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoriqueDette extends Model
{
    use SoftDeletes;

    /**
     * bascule : reste à verser de la veille ajouté automatiquement à la dette ;
     * annulation : remise (partielle ou totale) enregistrée par un admin.
     */
    public const TYPES = ['bascule', 'annulation'];

    protected $fillable = [
        'gestionnaire_id', 'gestionnaire_nom', 'type', 'montant',
        'dette_avant', 'dette_apres', 'motif', 'date_reference', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'dette_avant' => 'decimal:2',
            'dette_apres' => 'decimal:2',
            'date_reference' => 'date',
        ];
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionnaire_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
