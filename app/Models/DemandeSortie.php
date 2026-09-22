<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandeSortie extends Model
{
    use SoftDeletes;

    protected $table = 'demandes_sortie';

    /**
     * en_attente : soumise, pas encore traitée ; validee : transformée en sortie de
     * stock par le gestionnaire de stock ; rejetee : refusée (motif en commentaire_traitement).
     */
    public const STATUTS = ['en_attente', 'validee', 'rejetee'];

    protected $fillable = [
        'reference', 'vehicule_id', 'vehicule_code', 'motif', 'date_demande', 'statut',
        'demandeur_id', 'demandeur_nom', 'traite_par_id', 'traite_par_nom',
        'commentaire_traitement', 'sortie_id',
    ];

    protected function casts(): array
    {
        return [
            'date_demande' => 'date',
        ];
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par_id');
    }

    public function sortie(): BelongsTo
    {
        return $this->belongsTo(SortieStock::class, 'sortie_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(DemandeSortieLigne::class);
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', 'en_attente');
    }
}
