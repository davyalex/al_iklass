<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicule extends Model
{
    /** @use HasFactory<\Database\Factories\VehiculeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }
}
