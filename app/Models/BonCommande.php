<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BonCommande extends Model
{
    use SoftDeletes;

    protected $table = 'bons_commande';

    /**
     * en_attente : rien reçu ; partiellement_recu : au moins une ligne partiellement honorée ;
     * recu : toutes les lignes intégralement honorées ; annule : commande abandonnée.
     */
    public const STATUTS = ['en_attente', 'partiellement_recu', 'recu', 'annule'];

    protected $fillable = [
        'reference', 'fournisseur_id', 'fournisseur_nom', 'date_commande',
        'statut', 'commentaire', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BonCommandeLigne::class);
    }

    public function achats(): HasMany
    {
        return $this->hasMany(Achat::class);
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereIn('statut', ['en_attente', 'partiellement_recu']);
    }

    /**
     * Recalcule le statut à partir des quantités reçues sur chaque ligne.
     * N'écrase jamais un statut 'annule'.
     */
    public function recalculerStatut(): void
    {
        if ($this->statut === 'annule') {
            return;
        }

        $lignes = $this->lignes;

        $toutRecu = $lignes->every(fn (BonCommandeLigne $l) => $l->quantite_recue >= $l->quantite_commandee);
        $rienRecu = $lignes->every(fn (BonCommandeLigne $l) => $l->quantite_recue <= 0);

        $statut = match (true) {
            $toutRecu => 'recu',
            $rienRecu => 'en_attente',
            default => 'partiellement_recu',
        };

        if ($statut !== $this->statut) {
            $this->update(['statut' => $statut]);
        }
    }
}
