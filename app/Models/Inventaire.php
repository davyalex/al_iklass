<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventaire extends Model
{
    use SoftDeletes;

    /**
     * brouillon : comptage en cours, rien n'est encore appliqué au stock ;
     * valide : écarts appliqués au stock, immuable.
     */
    public const STATUTS = ['brouillon', 'valide'];

    protected $fillable = [
        'reference', 'categorie_id', 'date_inventaire', 'statut',
        'commentaire', 'user_id', 'valide_par_id', 'valide_le',
    ];

    protected function casts(): array
    {
        return [
            'date_inventaire' => 'date',
            'valide_le' => 'datetime',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieArticle::class, 'categorie_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function valideParUtilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(InventaireLigne::class)->orderBy('article_nom');
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }
}
