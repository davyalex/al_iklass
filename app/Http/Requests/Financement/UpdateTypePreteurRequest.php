<?php

namespace App\Http\Requests\Financement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypePreteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financements.type.gerer');
    }

    public function rules(): array
    {
        return [
            // Le code n'est pas modifiable une fois créé (identifiant stable,
            // référencé en snapshot sur chaque prêteur).
            'libelle' => ['required', 'string', 'max:100'],
            'actif' => ['boolean'],
        ];
    }
}
