<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypePanneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('interventions.type.gerer');
    }

    public function rules(): array
    {
        return [
            // Le code n'est pas modifiable une fois créé (identifiant stable,
            // référencé en snapshot sur chaque intervention).
            'libelle' => ['required', 'string', 'max:100'],
            'actif' => ['boolean'],
        ];
    }
}
