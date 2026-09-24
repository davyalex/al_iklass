<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypePanne extends Model
{
    protected $table = 'types_panne';

    protected $fillable = ['code', 'libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
