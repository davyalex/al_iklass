<x-guest-layout>
    <h1 class="h4 text-center mb-4" style="color: var(--al-navy);">Connexion</h1>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label">Se souvenir de moi</label>
        </div>

        <div class="d-grid gap-2">
            <x-primary-button>Se connecter</x-primary-button>
        </div>

        @if (Route::has('password.request'))
            <div class="text-center mt-3">
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
