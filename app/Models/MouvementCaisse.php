<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MouvementCaisse extends Model
{
    use SoftDeletes;

    protected $table = 'mouvements_caisse';

    protected $fillable = [
        'caisse_id', 'sens', 'montant', 'mode_paiement_id', 'reference',
        'motif', 'user_id', 'date_mouvement',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_mouvement' => 'datetime',
        ];
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function modePaiement(): BelongsTo
    {
        return $this->belongsTo(ModePaiement::class);
    }

    public function origine(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
