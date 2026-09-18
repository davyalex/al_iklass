<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventaireLigne extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inventaire_id', 'article_id', 'article_reference', 'article_nom',
        'quantite_theorique', 'quantite_comptee', 'prix_achat_unitaire', 'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'prix_achat_unitaire' => 'decimal:2',
        ];
    }

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function ecart(): ?int
    {
        if ($this->quantite_comptee === null) {
            return null;
        }

        return $this->quantite_comptee - $this->quantite_theorique;
    }
}
