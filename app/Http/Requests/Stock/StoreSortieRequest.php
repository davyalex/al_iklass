<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSortieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->input('nature') === 'externe'
            ? $this->user()->can('stock.sortie.vente')
            : $this->user()->can('stock.sortie.interne');
    }

    public function rules(): array
    {
        $estExterne = $this->input('nature') === 'externe';

        return [
            'nature' => ['required', Rule::in(['interne', 'externe'])],
            'motif' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date_sortie' => ['nullable', 'date'],
            'vehicule_id' => ['required_if:nature,interne', 'nullable', 'exists:vehicules,id'],
            'vehicule_externe' => ['required_if:nature,externe', 'nullable', 'string', 'max:100'],
            'acheteur' => ['required_if:nature,externe', 'nullable', 'string', 'max:255'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.article_id' => ['required', 'exists:articles,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_vente' => [$estExterne ? 'required' : 'nullable', 'numeric', 'min:0'],
        ];
    }
}
