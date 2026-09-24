<?php

namespace App\Http\Requests\Financement;

use Illuminate\Foundation\Http\FormRequest;

class StorePreteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financements.preteur.gerer');
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'type_preteur_id' => ['required', 'exists:types_preteur,id'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'actif' => ['boolean'],
        ];
    }
}
