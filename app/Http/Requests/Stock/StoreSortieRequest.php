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
        return [
            'nature' => ['required', Rule::in(['interne', 'externe'])],
            'article_id' => ['required', 'exists:articles,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'motif' => ['required', 'string', 'max:255'],
            'vehicule_id' => ['required_if:nature,interne', 'nullable', 'exists:vehicules,id'],
            'prix_vente' => ['required_if:nature,externe', 'nullable', 'numeric', 'min:0'],
            'vehicule_externe' => ['required_if:nature,externe', 'nullable', 'string', 'max:100'],
            'acheteur' => ['required_if:nature,externe', 'nullable', 'string', 'max:255'],
        ];
    }
}
