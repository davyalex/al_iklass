<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('unites.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:50', 'unique:unites,libelle'],
            'actif' => ['boolean'],
        ];
    }
}
