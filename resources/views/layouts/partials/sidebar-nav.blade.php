@php
    $logoApplication = \App\Models\Parametre::valeur('application.logo');
    $nomApplication = \App\Models\Parametre::valeur('application.nom', 'AL-IKLASS');
@endphp

<div class="d-flex align-items-center gap-2 px-3 py-3 mb-2">
    <span class="d-inline-flex align-items-center justify-content-center rounded-circle overflow-hidden"
          style="width: 36px; height: 36px; background-color: rgba(255,255,255,0.15); color: #fff;">
        @if ($logoApplication)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoApplication) }}" alt="{{ $nomApplication }}" class="w-100 h-100" style="object-fit: cover;">
        @else
            <i class="bi bi-truck fs-6"></i>
        @endif
    </span>
    <span class="fw-bold text-white fs-6">{{ $nomApplication }}</span>
</div>

<ul class="nav nav-pills flex-column mb-auto px-2 gap-1">
    <li class="nav-item">
        <a href="{{ route('dashboard') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Tableau de bord</span>
        </a>
    </li>

    @can('stock.tableau_bord.voir')
        <li class="nav-item mt-3">
            <span class="px-3 text-uppercase small fw-semibold" style="color: rgba(255,255,255,0.45); letter-spacing: .04em;">Stock</span>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.articles.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.articles.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Articles</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.etat-stock.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.etat-stock.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i>
                <span>Suivi de stock</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.mouvements.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.mouvements.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i>
                <span>Mouvements</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.fournisseurs.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.fournisseurs.*') ? 'active' : '' }}">
                <i class="bi bi-truck-front"></i>
                <span>Fournisseurs</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.bons-commande.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.bons-commande.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i>
                <span>Bons de commande</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.achats.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.achats.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i>
                <span>Achats (réceptions)</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.paiements.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.paiements.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>Paiements</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.sorties.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.sorties.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Sorties</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock.inventaires.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.inventaires.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i>
                <span>Inventaires</span>
            </a>
        </li>
    @endcan

    @canany(['flotte.vehicule.voir', 'flotte.vehicule.voir_affectes', 'flotte.vehicule.remise_circulation'])
        <li class="nav-item mt-3">
            <span class="px-3 text-uppercase small fw-semibold" style="color: rgba(255,255,255,0.45); letter-spacing: .04em;">Flotte</span>
        </li>
        @canany(['flotte.vehicule.voir', 'flotte.vehicule.voir_affectes', 'flotte.vehicule.remise_circulation'])
            <li class="nav-item">
                <a href="{{ route('flotte.vehicules.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('flotte.vehicules.*') ? 'active' : '' }}">
                    <i class="bi bi-truck-front-fill"></i>
                    <span>Véhicules</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('flotte.etat-parc.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('flotte.etat-parc.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-week"></i>
                    <span>État du parc</span>
                </a>
            </li>
        @endcanany
        @can('flotte.vehicule.voir')
            <li class="nav-item">
                <a href="{{ route('flotte.gestionnaires.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('flotte.gestionnaires.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span>Gestionnaires</span>
                </a>
            </li>
        @endcan
    @endcanany

    @can('operations.voir')
        <li class="nav-item mt-3">
            <span class="px-3 text-uppercase small fw-semibold" style="color: rgba(255,255,255,0.45); letter-spacing: .04em;">Entretien</span>
        </li>
        <li class="nav-item">
            <a href="{{ route('flotte.operations.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('flotte.operations.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i>
                <span>Opérations programmées</span>
            </a>
        </li>
    @endcan

    {{-- Argent : versements/dette (par gestionnaire ou vue admin) et le registre
    des 4 caisses — regroupés car transverses à Flotte et Stock, indépendamment
    de la section d'où provient chaque mouvement. --}}
    @canany(['flotte.vehicule.voir', 'flotte.versement.gerer', 'flotte.dette.gerer', 'flotte.dette.regler', 'caisse.voir'])
        <li class="nav-item mt-3">
            <span class="px-3 text-uppercase small fw-semibold" style="color: rgba(255,255,255,0.45); letter-spacing: .04em;">Finance</span>
        </li>
        @canany(['flotte.vehicule.voir', 'flotte.versement.gerer'])
            <li class="nav-item">
                <a href="{{ route('flotte.versements.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('flotte.versements.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i>
                    <span>Versements</span>
                </a>
            </li>
        @endcanany
        @canany(['flotte.dette.gerer', 'flotte.dette.regler'])
            <li class="nav-item">
                <a href="{{ route('flotte.dettes.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('flotte.dettes.*') ? 'active' : '' }}">
                    <i class="bi bi-exclamation-octagon"></i>
                    <span>Dette</span>
                </a>
            </li>
        @endcanany
        @can('caisse.voir')
            <li class="nav-item">
                <a href="{{ route('admin.caisses.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.caisses.*') ? 'active' : '' }}">
                    <i class="bi bi-wallet2"></i>
                    <span>Caisses</span>
                </a>
            </li>
        @endcan
    @endcanany

    @canany(['utilisateurs.voir', 'roles.voir', 'unites.voir', 'audit.voir', 'parametres.voir'])
        <li class="nav-item mt-3">
            <span class="px-3 text-uppercase small fw-semibold" style="color: rgba(255,255,255,0.45); letter-spacing: .04em;">Administration</span>
        </li>
        @can('utilisateurs.voir')
            <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    <span>Utilisateurs</span>
                </a>
            </li>
        @endcan
        @can('roles.voir')
            <li class="nav-item">
                <a href="{{ route('admin.roles.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i>
                    <span>Rôles</span>
                </a>
            </li>
        @endcan
        @can('unites.voir')
            <li class="nav-item">
                <a href="{{ route('admin.unites.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.unites.*') ? 'active' : '' }}">
                    <i class="bi bi-rulers"></i>
                    <span>Unités</span>
                </a>
            </li>
        @endcan
        @can('audit.voir')
            <li class="nav-item">
                <a href="{{ route('admin.audit.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Journal d'audit</span>
                </a>
            </li>
        @endcan
        @can('parametres.voir')
            <li class="nav-item">
                <a href="{{ route('admin.parametres.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.parametres.*') ? 'active' : '' }}">
                    <i class="bi bi-sliders"></i>
                    <span>Paramètres</span>
                </a>
            </li>
        @endcan
    @endcanany
</ul>
