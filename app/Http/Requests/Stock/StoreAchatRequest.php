<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

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
}
