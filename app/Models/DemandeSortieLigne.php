<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandeSortieLigne extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'demande_sortie_id', 'article_id', 'article_reference', 'article_nom', 'quantite',
    ];

    public function demandeSortie(): BelongsTo
    {
        return $this->belongsTo(DemandeSortie::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
