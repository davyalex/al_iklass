<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoriqueStatutVehicule extends Model
{
    use SoftDeletes;

    protected $table = 'historique_statuts_vehicule';

    protected $fillable = [
        'vehicule_id', 'vehicule_code',
        'ancien_statut_id', 'ancien_statut_code', 'ancien_statut_libelle',
        'nouveau_statut_id', 'nouveau_statut_code', 'nouveau_statut_libelle',
        'user_id', 'commentaire',
    ];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function ancienStatut(): BelongsTo
    {
        return $this->belongsTo(StatutVehicule::class, 'ancien_statut_id');
    }

    public function nouveauStatut(): BelongsTo
    {
        return $this->belongsTo(StatutVehicule::class, 'nouveau_statut_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
