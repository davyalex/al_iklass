<?php

namespace App\Http\Requests\Financement;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypePreteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financements.type.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:100'],
            'actif' => ['boolean'],
        ];
    }
}
