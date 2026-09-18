<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComptageInventaireLigneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.inventaire.gerer');
    }

    public function rules(): array
    {
        return [
            'quantite_comptee' => ['nullable', 'integer', 'min:0'],
            'commentaire' => ['nullable', 'string', 'max:255'],
        ];
    }
}
