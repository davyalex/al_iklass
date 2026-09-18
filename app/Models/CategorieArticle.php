<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorieArticle extends Model
{
    use SoftDeletes;

    protected $table = 'categories_article';

    protected $fillable = ['code', 'libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'categorie_id');
    }
}
