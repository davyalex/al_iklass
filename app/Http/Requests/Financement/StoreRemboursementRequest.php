<?php

namespace App\Http\Requests\Financement;

use App\Models\Financement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRemboursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('financements.rembourser');
    }

    public function rules(): array
    {
        return [
            'financement_id' => ['required', 'exists:financements,id'],
            'date_remboursement' => ['nullable', 'date'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement_id' => ['nullable', 'exists:modes_paiement,id'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $financement = Financement::find($this->input('financement_id'));

            if ($financement && $this->filled('montant') && (float) $this->input('montant') > (float) $financement->montant_restant) {
                $validator->errors()->add('montant', 'Ce montant dépasse le solde restant dû pour ce financement.');
            }
        });
    }
}
