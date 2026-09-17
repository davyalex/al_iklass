<x-guest-layout>
    <h1 class="h4 text-center mb-3" style="color: var(--al-navy);">Mot de passe oublié</h1>

    <p class="small text-muted mb-4">
        Indiquez votre adresse e-mail : nous vous enverrons un lien pour réinitialiser votre mot de passe.
    </p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="d-grid gap-2">
            <x-primary-button>Envoyer le lien de réinitialisation</x-primary-button>
        </div>
    </form>
</x-guest-layout>
