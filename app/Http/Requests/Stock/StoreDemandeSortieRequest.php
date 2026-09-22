<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeSortieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.demande.creer');
    }

    public function rules(): array
    {
        return [
            'vehicule_id' => ['required', 'exists:vehicules,id'],
            'motif' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date_demande' => ['nullable', 'date'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.article_id' => ['required', 'exists:articles,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
        ];
    }
}
