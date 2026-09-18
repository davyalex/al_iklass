<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.article.gerer');
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:50', 'unique:articles,reference'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'categorie_id' => ['nullable', 'exists:categories_article,id'],
            'unite_id' => ['nullable', 'exists:unites,id'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }
}
