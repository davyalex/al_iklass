<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatutVehicule extends Model
{
    protected $table = 'statuts_vehicule';

    protected $fillable = ['code', 'libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function vehicules(): HasMany
    {
        return $this->hasMany(Vehicule::class, 'statut_id');
    }
}
