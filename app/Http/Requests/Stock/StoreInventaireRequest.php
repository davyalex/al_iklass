<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.inventaire.gerer');
    }

    public function rules(): array
    {
        return [
            'categorie_id' => ['nullable', 'exists:categories_article,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date_inventaire' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string'],
        ];
    }
}
