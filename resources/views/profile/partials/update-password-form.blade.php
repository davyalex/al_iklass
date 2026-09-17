<section>
    <header class="mb-3">
        <h2 class="h5 mb-1">Modifier le mot de passe</h2>
        <p class="small text-muted mb-0">Utilisez un mot de passe long et unique pour sécuriser votre compte.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" value="Mot de passe actuel" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" value="Nouveau mot de passe" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" value="Confirmer le mot de passe" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>Enregistrer</x-primary-button>

            @if (session('status') === 'password-updated')
                <span class="small text-success">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
