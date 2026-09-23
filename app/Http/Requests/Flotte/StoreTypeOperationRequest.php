<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('operations.type.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:100'],
            'periodicite_jours' => ['nullable', 'integer', 'min:1'],
            'actif' => ['boolean'],
        ];
    }
}
