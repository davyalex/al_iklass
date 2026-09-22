<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerDetteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('flotte.dette.gerer');
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'gt:0'],
            'motif' => ['required', 'string', 'max:500'],
        ];
    }
}
