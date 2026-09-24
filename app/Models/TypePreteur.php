<?php

namespace App\Models;

use Database\Factories\TypePreteurFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypePreteur extends Model
{
    /** @use HasFactory<TypePreteurFactory> */
    use HasFactory;

    protected $table = 'types_preteur';

    protected $fillable = ['code', 'libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function preteurs(): HasMany
    {
        return $this->hasMany(Preteur::class);
    }
}
