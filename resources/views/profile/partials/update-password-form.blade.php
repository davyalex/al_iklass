<section>
    <header class="mb-3">
        <h2 class="h6 mb-1">Changer mon mot de passe</h2>
        <p class="small text-muted mb-0">Au moins 6 caractères.</p>
    </header>

    <form method="post" action="{{ route('profile.password.update') }}">
        @csrf
        @method('put')

        <div class="row g-3">
            <div class="col-12">
                <x-input-label for="current_password" value="Mot de passe actuel" />
                <x-text-input id="current_password" name="current_password" type="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('current_password')" />
            </div>

            <div class="col-12 col-md-6">
                <x-input-label for="password" value="Nouveau mot de passe" />
                <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="col-12 col-md-6">
                <x-input-label for="password_confirmation" value="Confirmer le nouveau mot de passe" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 mt-3">
            <x-primary-button>Mettre à jour</x-primary-button>

            @if (session('status') === 'password-updated')
                <span class="small text-success"><i class="bi bi-check-circle me-1"></i>Mot de passe modifié.</span>
            @endif
        </div>
    </form>
</section>
