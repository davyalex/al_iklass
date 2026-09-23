<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeOperation extends Model
{
    protected $table = 'types_operation';

    protected $fillable = ['code', 'libelle', 'periodicite_jours', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function operationsProgrammees(): HasMany
    {
        return $this->hasMany(OperationProgrammee::class);
    }
}
