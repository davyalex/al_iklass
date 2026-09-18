<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreBonCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.bon_commande.gerer');
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => ['required', 'exists:fournisseurs,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date_commande' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.article_id' => ['required', 'exists:articles,id'],
            'lignes.*.quantite_commandee' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_unitaire_estime' => ['required', 'numeric', 'min:0'],
        ];
    }
}
