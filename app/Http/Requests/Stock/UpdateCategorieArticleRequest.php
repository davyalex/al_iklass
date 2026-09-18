<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategorieArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.article.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'actif' => ['boolean'],
        ];
    }
}
