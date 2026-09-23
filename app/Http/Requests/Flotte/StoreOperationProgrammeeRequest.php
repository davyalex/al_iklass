<?php

namespace App\Http\Requests\Flotte;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationProgrammeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('operations.gerer');
    }

    public function rules(): array
    {
        return [
            'vehicule_id' => ['required', 'integer', 'exists:vehicules,id'],
            'type_operation_id' => ['required', 'integer', 'exists:types_operation,id'],
            'date_echeance' => ['required', 'date'],
            'rappel_jours' => ['required', 'integer', 'min:0'],
            'periodicite_jours' => ['nullable', 'integer', 'min:1'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }
}
