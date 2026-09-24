<?php

namespace App\Http\Requests\Financement;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financements.gerer');
    }

    public function rules(): array
    {
        return [
            'preteur_id' => ['required', 'exists:preteurs,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date_financement' => ['nullable', 'date'],
            'montant_total' => ['required', 'numeric', 'min:0.01'],
            'commentaire' => ['nullable', 'string'],
        ];
    }
}
