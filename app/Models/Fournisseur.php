<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fournisseur extends Model
{
    /** @use HasFactory<\Database\Factories\FournisseurFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['nom', 'telephone', 'email', 'adresse', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function achats(): HasMany
    {
        return $this->hasMany(Achat::class);
    }
}
