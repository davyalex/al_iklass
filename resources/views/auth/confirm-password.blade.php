<x-guest-layout>
    <p class="small text-muted mb-4">
        Zone sécurisée : veuillez confirmer votre mot de passe avant de continuer.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="d-grid gap-2">
            <x-primary-button>Confirmer</x-primary-button>
        </div>
    </form>
</x-guest-layout>
