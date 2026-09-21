<?php

namespace App\Models;

use Database\Factories\VehiculeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicule extends Model
{
    /** @use HasFactory<VehiculeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Propriété PHP « normale » (pas un attribut Eloquent) utilisée pour
     * transmettre ponctuellement un commentaire à VehiculeObserver lors d'un
     * changement de statut (ex. rapport de remise en circulation), sans que
     * cette valeur soit jamais persistée sur la table vehicules elle-même.
     */
    public ?string $commentaireHistorique = null;

    protected $fillable = [
        'code',
        'libelle',
        'actif',
        'marque',
        'modele',
        'immatriculation',
        'date_mise_circulation',
        'statut_id',
        'chauffeur_nom',
        'chauffeur_telephone',
        'recette_journaliere',
        'gestionnaire_id',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'date_mise_circulation' => 'date',
            'recette_journaliere' => 'decimal:2',
        ];
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function statut(): BelongsTo
    {
        return $this->belongsTo(StatutVehicule::class, 'statut_id');
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionnaire_id');
    }

    public function historiqueStatuts(): HasMany
    {
        return $this->hasMany(HistoriqueStatutVehicule::class);
    }
}
