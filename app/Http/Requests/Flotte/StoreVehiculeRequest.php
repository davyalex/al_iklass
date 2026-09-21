<?php

namespace App\Http\Requests\Flotte;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehiculeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('flotte.vehicule.gerer');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:vehicules,code'],
            'libelle' => ['required', 'string', 'max:255'],
            'marque' => ['nullable', 'string', 'max:50'],
            'modele' => ['nullable', 'string', 'max:50'],
            'immatriculation' => ['nullable', 'string', 'max:20', 'unique:vehicules,immatriculation'],
            'date_mise_circulation' => ['nullable', 'date'],
            'statut_id' => ['required', 'exists:statuts_vehicule,id'],
            'chauffeur_nom' => ['nullable', 'string', 'max:255'],
            'chauffeur_telephone' => ['nullable', 'string', 'max:20'],
            'recette_journaliere' => ['required', 'numeric', 'min:0'],
            'gestionnaire_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && ! User::whereKey($value)->role('gestionnaire')->exists()) {
                        $fail('L\'utilisateur sélectionné n\'a pas le rôle « gestionnaire ».');
                    }
                },
            ],
        ];
    }
}
