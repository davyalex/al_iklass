<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class RejeterDemandeSortieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.demande.traiter');
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'max:1000'],
        ];
    }
}
