<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class ClotureInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('interventions.declarer');
    }

    public function rules(): array
    {
        return [
            'rapport' => ['required', 'string', 'max:1000'],
            'date_fin' => ['nullable', 'date'],
        ];
    }
}
