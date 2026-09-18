<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('unites.gerer');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:50', Rule::unique('unites', 'libelle')->ignore($this->route('unite'))],
            'actif' => ['boolean'],
        ];
    }
}
