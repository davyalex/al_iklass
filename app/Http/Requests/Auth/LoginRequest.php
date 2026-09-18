<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('username', $this->string('username'))->first();

        if ($user && $user->isLocked()) {
            throw ValidationException::withMessages([
                'username' => 'Ce compte est verrouillé après plusieurs tentatives échouées. Contactez un administrateur.',
            ]);
        }

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => 'Ce compte est désactivé. Contactez un administrateur.',
            ]);
        }

        if (! $user || ! Auth::attempt(['username' => $this->string('username'), 'password' => $this->string('password')])) {
            RateLimiter::hit($this->throttleKey());

            $user?->registerFailedLogin();

            if ($user?->isLocked()) {
                throw ValidationException::withMessages([
                    'username' => 'Ce compte vient d\'être verrouillé après 3 tentatives échouées. Contactez un administrateur.',
                ]);
            }

            throw ValidationException::withMessages([
                'username' => 'Ces identifiants ne correspondent à aucun compte.',
            ]);
        }

        $user->clearFailedLogins();

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Trop de tentatives de connexion. Réessayez dans {$seconds} secondes.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
