<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('utilisateurs.gerer');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'digits:10', 'unique:users,telephone'],
            // Seul un superadmin peut créer un autre superadmin — un admin ne
            // doit ni le voir ni pouvoir l'attribuer.
            'role' => ['required', 'string', 'exists:roles,name', Rule::notIn($this->user()->hasRole('superadmin') ? [] : ['superadmin'])],
        ];
    }
}
