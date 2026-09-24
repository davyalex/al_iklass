<section>
    <header class="mb-3">
        <h2 class="h6 mb-1">Modifier mes informations</h2>
        <p class="small text-muted mb-0">Nom, identifiant de connexion et coordonnées.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <x-input-label for="name" value="Nom complet" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="col-12 col-md-6">
                <x-input-label for="username" value="Identifiant de connexion" />
                <x-text-input id="username" name="username" type="text" :value="old('username', $user->username)" required autocomplete="username" />
                <x-input-error :messages="$errors->get('username')" />
            </div>

            <div class="col-12 col-md-6">
                <x-input-label for="telephone" value="Téléphone" />
                <x-text-input id="telephone" name="telephone" type="tel" inputmode="numeric" maxlength="10" :value="old('telephone', $user->telephone)" required autocomplete="tel" />
                <x-input-error :messages="$errors->get('telephone')" />
            </div>

            <div class="col-12 col-md-6">
                <x-input-label for="email" value="Adresse e-mail (facultatif)" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 mt-3">
            <x-primary-button>Enregistrer</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="small text-success"><i class="bi bi-check-circle me-1"></i>Informations enregistrées.</span>
            @endif
        </div>
    </form>
</section>
