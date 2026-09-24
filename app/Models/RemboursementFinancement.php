<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RemboursementFinancement extends Model
{
    use SoftDeletes;

    protected $table = 'remboursements_financement';

    protected $fillable = [
        'financement_id', 'preteur_nom', 'date_remboursement', 'montant',
        'mode_paiement_id', 'reference', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_remboursement' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function financement(): BelongsTo
    {
        return $this->belongsTo(Financement::class);
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
