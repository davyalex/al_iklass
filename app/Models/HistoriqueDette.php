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
     * annulation : remise (partielle ou totale) enregistrée par un admin, sans
     * mouvement d'argent réel ; reglement : paiement réel du gestionnaire
     * (ou saisi par un admin en son nom), qui écrit un mouvement_caisse.
     */
    public const TYPES = ['bascule', 'annulation', 'reglement'];

    protected $fillable = [
        'gestionnaire_id', 'gestionnaire_nom', 'type', 'montant',
        'dette_avant', 'dette_apres', 'motif', 'date_reference',
        'attendu', 'deja_verse', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'dette_avant' => 'decimal:2',
            'dette_apres' => 'decimal:2',
            'date_reference' => 'date',
            'attendu' => 'decimal:2',
            'deja_verse' => 'decimal:2',
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
