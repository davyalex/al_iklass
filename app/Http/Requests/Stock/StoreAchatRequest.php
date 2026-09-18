<?php

namespace App\Http\Requests\Stock;

use App\Models\BonCommandeLigne;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.achat.gerer');
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => ['required', 'exists:fournisseurs,id'],
            'bon_commande_id' => ['nullable', 'exists:bons_commande,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date_achat' => ['nullable', 'date'],
            'montant_paye' => ['nullable', 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.article_id' => ['required', 'exists:articles,id'],
            'lignes.*.bon_commande_ligne_id' => ['nullable', 'exists:bon_commande_lignes,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Réception liée à un bon de commande : on ne peut réceptionner que les lignes de ce bon
     * de commande, et jamais au-delà de la quantité restant à recevoir sur chacune.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $bonCommandeId = $this->input('bon_commande_id');

            if (! $bonCommandeId) {
                return;
            }

            $lignes = $this->input('lignes', []);
            $quantiteDemandeeParLigne = [];

            foreach ($lignes as $index => $ligne) {
                $bonCommandeLigneId = $ligne['bon_commande_ligne_id'] ?? null;

                if (! $bonCommandeLigneId) {
                    $validator->errors()->add(
                        "lignes.{$index}.bon_commande_ligne_id",
                        "Cet article ne fait pas partie du bon de commande : impossible de l'ajouter à la réception."
                    );

                    continue;
                }

                $bonCommandeLigne = BonCommandeLigne::find($bonCommandeLigneId);

                if (! $bonCommandeLigne || (string) $bonCommandeLigne->bon_commande_id !== (string) $bonCommandeId) {
                    $validator->errors()->add(
                        "lignes.{$index}.bon_commande_ligne_id",
                        "Cette ligne n'appartient pas au bon de commande sélectionné."
                    );

                    continue;
                }

                $quantiteDemandeeParLigne[$bonCommandeLigneId] ??= 0;
                $quantiteDemandeeParLigne[$bonCommandeLigneId] += (int) ($ligne['quantite'] ?? 0);
            }

            foreach ($quantiteDemandeeParLigne as $bonCommandeLigneId => $quantiteDemandee) {
                $bonCommandeLigne = BonCommandeLigne::find($bonCommandeLigneId);
                $restant = $bonCommandeLigne->quantiteRestante();

                if ($quantiteDemandee > $restant) {
                    $validator->errors()->add(
                        'lignes',
                        "La quantité reçue pour « {$bonCommandeLigne->article_nom} » ({$quantiteDemandee}) dépasse la quantité restant à recevoir ({$restant})."
                    );
                }
            }
        });
    }
}
