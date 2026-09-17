<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.article.manage');
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:50', Rule::unique('articles', 'reference')->ignore($this->route('article'))],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'categorie_id' => ['nullable', 'exists:categories_article,id'],
            'unite' => ['nullable', 'string', 'max:50'],
            'prix_vente' => ['nullable', 'numeric', 'min:0'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }
}
