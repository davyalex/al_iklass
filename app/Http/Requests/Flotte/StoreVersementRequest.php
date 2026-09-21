<?php

namespace App\Http\Requests\Flotte;

use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVersementRequest extends FormRequest
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
            'gestionnaire_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && ! User::whereKey($value)->role('gestionnaire')->exists()) {
                        $fail('L\'utilisateur sélectionné n\'a pas le rôle « gestionnaire ».');
                    }
                },
            ],
            'vehicule_id' => [
                'nullable',
                'integer',
                'exists:vehicules,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    $vehicule = Vehicule::find($value);

                    if ($vehicule && (string) $vehicule->gestionnaire_id !== (string) $this->input('gestionnaire_id')) {
                        $fail("Ce véhicule n'est pas affecté au gestionnaire sélectionné.");
                    }
                },
            ],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement_id' => ['required', 'exists:modes_paiement,id'],
            'date_versement' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:255'],
        ];
    }
}
