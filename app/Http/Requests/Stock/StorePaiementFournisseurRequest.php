<?php

namespace App\Http\Requests\Stock;

use App\Models\Achat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaiementFournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.paiement.manage');
    }

    public function rules(): array
    {
        return [
            'achat_id' => ['required', 'exists:achats,id'],
            'date_paiement' => ['nullable', 'date'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement_id' => ['required', 'exists:modes_paiement,id'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $achat = Achat::find($this->input('achat_id'));

            if ($achat && $this->filled('montant') && (float) $this->input('montant') > (float) $achat->montant_restant) {
                $validator->errors()->add('montant', 'Ce montant dépasse le solde restant dû pour cet achat.');
            }
        });
    }
}
