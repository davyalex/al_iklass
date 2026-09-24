@php
    $libellesRoles = [
        'superadmin' => 'Super administrateur',
        'admin' => 'Administrateur',
        'gestionnaire' => 'Gestionnaire (parc)',
        'gestionnaire_stock' => 'Gestionnaire de stock',
        'chef_mecanicien' => 'Chef mécanicien',
    ];
    $role = $user->roles->first()?->name;
    $initiales = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->implode('');
@endphp

<x-app-layout>
    <x-slot name="header">Mon profil</x-slot>

    <div class="row g-3">
        {{-- Identité + informations du compte --}}
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 bg-white mb-3">
                <div class="card-body p-4 text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-semibold fs-3 mb-3"
                         style="width: 72px; height: 72px; background-color: var(--al-navy);">
                        {{ $initiales }}
                    </div>
                    <h2 class="h5 mb-1">{{ $user->name }}</h2>
                    <div class="text-muted small mb-2">{{ '@'.$user->username }}</div>
                    <div class="d-flex justify-content-center flex-wrap gap-1">
                        <span class="badge text-bg-primary">{{ $libellesRoles[$role] ?? 'Sans rôle' }}</span>
                        @if ($user->is_active)
                            <span class="badge text-bg-success">Compte actif</span>
                        @else
                            <span class="badge text-bg-secondary">Compte désactivé</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4">
                    <h3 class="h6 mb-3">Informations du compte</h3>
                    <dl class="row small mb-0 g-0">
                        <dt class="col-5 text-muted fw-normal mb-2">Identifiant</dt>
                        <dd class="col-7 mb-2 text-break">{{ $user->username }}</dd>

                        <dt class="col-5 text-muted fw-normal mb-2">E-mail</dt>
                        <dd class="col-7 mb-2 text-break">{{ $user->email ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal mb-2">Téléphone</dt>
                        <dd class="col-7 mb-2">{{ $user->telephone ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal mb-2">Compte créé le</dt>
                        <dd class="col-7 mb-2">
                            {{ $user->created_at?->format('d/m/Y à H:i') ?? '—' }}
                            @if ($user->created_at)
                                <div class="text-muted">{{ $user->created_at->diffForHumans() }}</div>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal mb-2">Dernière modification</dt>
                        <dd class="col-7 mb-2">{{ $user->updated_at?->format('d/m/Y à H:i') ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal mb-2">Dernière connexion</dt>
                        <dd class="col-7 mb-2">{{ $user->derniere_connexion_at?->format('d/m/Y à H:i') ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal mb-2">Actions ce mois-ci</dt>
                        <dd class="col-7 mb-2">{{ $nombreActivitesDuMois }}</dd>

                        @if ($nombreVehiculesAttribues !== null)
                            <dt class="col-5 text-muted fw-normal mb-2">Véhicules attribués</dt>
                            <dd class="col-7 mb-2">{{ $nombreVehiculesAttribues }}</dd>

                            <dt class="col-5 text-muted fw-normal mb-0">Dette en cours</dt>
                            <dd class="col-7 mb-0 {{ (float) $user->dette > 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ \App\Support\Money::format($user->dette) }} FCFA
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Modification (admin uniquement) + activité récente --}}
        <div class="col-12 col-lg-7">
            @if ($peutModifier)
                <div class="card shadow-sm border-0 bg-white mb-3">
                    <div class="card-body p-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-white mb-3">
                    <div class="card-body p-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            @else
                <div class="alert alert-light border d-flex gap-2 mb-3">
                    <i class="bi bi-lock text-muted"></i>
                    <div class="small">
                        Votre profil est en lecture seule. Pour modifier vos informations ou votre mot de passe,
                        adressez-vous à un administrateur.
                    </div>
                </div>
            @endif

            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body p-4">
                    <h3 class="h6 mb-3">Mon activité récente</h3>
                    @forelse ($activitesRecentes as $activite)
                        @php($evenement = \App\Services\Admin\AuditService::EVENEMENTS[$activite->event] ?? ['libelle' => 'Action', 'couleur' => 'light'])
                        <div class="d-flex gap-2 align-items-start py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                            <span class="badge text-bg-{{ $evenement['couleur'] }} flex-shrink-0 mt-1">{{ $evenement['libelle'] }}</span>
                            <div class="small flex-grow-1 text-break">
                                <div>{{ $activite->description }}</div>
                                <div class="text-muted">{{ $activite->created_at->format('d/m/Y H:i') }} · {{ $activite->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Aucune activité enregistrée récemment.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
