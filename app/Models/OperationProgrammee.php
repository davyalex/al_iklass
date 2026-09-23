<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationProgrammee extends Model
{
    use SoftDeletes;

    protected $table = 'operations_programmees';

    /**
     * Table unique programmation + historique : réaliser une ligne 'planifiee'
     * la fait passer 'realisee' et en crée une nouvelle pour le cycle suivant
     * (OperationProgrammeeService::realiser). L'historique d'un véhicule est
     * simplement l'ensemble de ses lignes, triées par date.
     */
    public const STATUTS = ['planifiee', 'realisee'];

    protected $fillable = [
        'vehicule_id', 'vehicule_code',
        'type_operation_id', 'type_operation_code', 'type_operation_libelle',
        'date_echeance', 'rappel_jours', 'periodicite_jours',
        'statut', 'date_realisation', 'commentaire',
        'user_id', 'realise_par_id',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'date_realisation' => 'date',
        ];
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function typeOperation(): BelongsTo
    {
        return $this->belongsTo(TypeOperation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function realisePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realise_par_id');
    }

    /**
     * Badge d'alerte calculé à la volée (jamais stocké) : 'rouge' si l'échéance
     * est dépassée, 'jaune' si on est entré dans la fenêtre de rappel, sinon null.
     */
    public function badge(): ?string
    {
        if ($this->statut !== 'planifiee') {
            return null;
        }

        $aujourdhui = now()->startOfDay();

        if ($aujourdhui->gte($this->date_echeance)) {
            return 'rouge';
        }

        if ($aujourdhui->gte($this->date_echeance->copy()->subDays($this->rappel_jours))) {
            return 'jaune';
        }

        return null;
    }
}
