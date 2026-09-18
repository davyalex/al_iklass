<x-guest-layout>
    <h1 class="h4 text-center mb-4" style="color: var(--al-navy);">Connexion</h1>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="username" value="Nom d'utilisateur" />
            <x-text-input id="username" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Mot de passe" />
            <div class="input-group">
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <button type="button" class="btn btn-outline-secondary" id="btn-afficher-mdp" tabindex="-1" aria-label="Afficher le mot de passe">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="d-grid gap-2">
            <x-primary-button>Se connecter</x-primary-button>
        </div>
    </form>

    <script>
        document.getElementById('btn-afficher-mdp').addEventListener('click', function () {
            const champ = document.getElementById('password');
            const icone = this.querySelector('i');
            const visible = champ.type === 'text';

            champ.type = visible ? 'password' : 'text';
            icone.classList.toggle('bi-eye');
            icone.classList.toggle('bi-eye-slash');
        });
    </script>
</x-guest-layout>
