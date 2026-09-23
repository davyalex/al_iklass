<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class RealiserOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('operations.realiser');
    }

    public function rules(): array
    {
        return [
            'date_realisation' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }
}
