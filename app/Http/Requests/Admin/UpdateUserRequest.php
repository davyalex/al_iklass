<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('utilisateurs.gerer');
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'telephone' => ['required', 'digits:10', Rule::unique('users', 'telephone')->ignore($userId)],
            // Seul un superadmin peut promouvoir quelqu'un au rôle superadmin —
            // un admin ne doit ni le voir ni pouvoir l'attribuer.
            'role' => ['required', 'string', 'exists:roles,name', Rule::notIn($this->user()->hasRole('superadmin') ? [] : ['superadmin'])],
        ];
    }
}
