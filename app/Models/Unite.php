<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unite extends Model
{
    use SoftDeletes;

    protected $fillable = ['libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'unite_id');
    }
}
