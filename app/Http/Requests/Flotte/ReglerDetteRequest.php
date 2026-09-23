<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class ReglerDetteRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()->can('flotte.dette.gerer')) {
            return true;
        }

        // Un gestionnaire (flotte.dette.regler seul) ne peut régler que sa
        // propre dette, jamais celle d'un autre gestionnaire.
        return $this->user()->can('flotte.dette.regler')
            && (int) $this->route('gestionnaire')->id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'gt:0'],
            'motif' => ['nullable', 'string', 'max:500'],
        ];
    }
}
