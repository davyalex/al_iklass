<section>
    <header class="mb-3">
        <h2 class="h5 mb-1">Informations du profil</h2>
        <p class="small text-muted mb-0">Mettez à jour votre nom et votre adresse e-mail.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="name" value="Nom" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>Enregistrer</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="small text-success">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
