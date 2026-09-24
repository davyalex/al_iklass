<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypePanneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('interventions.type.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:100'],
            'actif' => ['boolean'],
        ];
    }
}
