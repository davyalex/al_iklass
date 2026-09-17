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

    @can('stock.dashboard.view')
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
            <a href="{{ route('stock.fournisseurs.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('stock.fournisseurs.*') ? 'active' : '' }}">
                <i class="bi bi-truck-front"></i>
                <span>Fournisseurs</span>
            </a>
        </li>
    @endcan
</ul>
