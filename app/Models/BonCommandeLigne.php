<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BonCommandeLigne extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bon_commande_id', 'article_id', 'article_reference', 'article_nom',
        'quantite_commandee', 'quantite_recue', 'prix_unitaire_estime', 'montant_estime',
    ];

    protected function casts(): array
    {
        return [
            'prix_unitaire_estime' => 'decimal:2',
            'montant_estime' => 'decimal:2',
        ];
    }

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function quantiteRestante(): int
    {
        return max(0, $this->quantite_commandee - $this->quantite_recue);
    }
}
