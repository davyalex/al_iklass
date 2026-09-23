<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('operations.type.gerer');
    }

    public function rules(): array
    {
        return [
            // Le code n'est pas modifiable une fois créé (identifiant stable,
            // référencé en snapshot sur chaque ligne operations_programmees).
            'libelle' => ['required', 'string', 'max:100'],
            'actif' => ['boolean'],
        ];
    }
}
