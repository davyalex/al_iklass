<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParametreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('parametres.gerer');
    }

    public function rules(): array
    {
        $cle = $this->route('parametre')?->cle ?? '';

        $formatHeure = str_contains($cle, '.heure_') ? ['date_format:H:i'] : [];
        $formatRetention = $cle === 'sauvegarde.retention' ? ['integer', 'min:1', 'max:365'] : [];

        return [
            'valeur' => ['required', 'string', 'max:255', ...$formatHeure, ...$formatRetention],
        ];
    }
}
