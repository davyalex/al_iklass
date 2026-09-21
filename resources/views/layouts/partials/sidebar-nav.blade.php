<div class="d-flex align-items-center gap-2 px-3 py-3 mb-2">
    <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
          style="width: 36px; height: 36px; background-color: rgba(255,255,255,0.15); color: #fff;">
        <i class="bi bi-truck fs-6"></i>
    </span>
    <span class="fw-bold text-white fs-6">AL-IKLASS</span>
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

    @canany(['utilisateurs.voir', 'roles.voir', 'unites.voir', 'audit.voir'])
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
    @endcanany
</ul>
