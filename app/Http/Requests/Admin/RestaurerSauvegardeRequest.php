<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RestaurerSauvegardeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('superadmin');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
