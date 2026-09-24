<?php

namespace App\Models;

use Database\Factories\PreteurFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Preteur extends Model
{
    /** @use HasFactory<PreteurFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom', 'type_preteur_id', 'type_preteur_code', 'type_preteur_libelle',
        'telephone', 'email', 'adresse', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function typePreteur(): BelongsTo
    {
        return $this->belongsTo(TypePreteur::class);
    }

    public function financements(): HasMany
    {
        return $this->hasMany(Financement::class);
    }
}
