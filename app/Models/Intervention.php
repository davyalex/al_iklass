<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    use SoftDeletes;

    /**
     * Déclarée par le chef mécanicien quand un véhicule arrive au garage
     * (statut du véhicule changé dans la même action) ; clôturée en
     * arrière-plan par la remise en circulation existante (rapport
     * obligatoire déjà en place), pas par un écran dédié.
     */
    public const STATUTS = ['en_cours', 'terminee'];

    protected $fillable = [
        'vehicule_id', 'vehicule_code',
        'type_panne_id', 'type_panne_libelle',
        'description', 'statut', 'date_debut', 'date_fin', 'rapport',
        'declaree_par_id', 'cloturee_par_id',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
        ];
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function typePanne(): BelongsTo
    {
        return $this->belongsTo(TypePanne::class);
    }

    public function declareePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declaree_par_id');
    }

    public function clotureePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloturee_par_id');
    }
}
