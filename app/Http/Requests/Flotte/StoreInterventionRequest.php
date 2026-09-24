<?php

namespace App\Http\Requests\Flotte;

use App\Models\StatutVehicule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('interventions.declarer');
    }

    public function rules(): array
    {
        return [
            'vehicule_id' => ['required', 'integer', 'exists:vehicules,id'],
            'type_panne_id' => ['nullable', 'integer', 'exists:types_panne,id'],
            'description' => ['required', 'string', 'max:1000'],
            'statut_id' => ['required', 'integer', 'exists:statuts_vehicule,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('statut_id')) {
                return;
            }

            // Déclarer une panne doit sortir le véhicule de la circulation,
            // pas l'y laisser.
            $code = StatutVehicule::whereKey($this->integer('statut_id'))->value('code');

            if ($code === 'en_circulation') {
                $validator->errors()->add('statut_id', 'Le statut ne peut pas rester "En circulation" pour une panne déclarée.');
            }
        });
    }
}
