@php
    $initialesUtilisateur = collect(explode(' ', trim(Auth::user()->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
        ->implode('');
    $notificationsNonLues = 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.head-identite')

        {{-- Applique l'état « sidebar réduite » avant le premier rendu (évite un saut visuel).
             Sans choix mémorisé, elle est réduite d'office sur tablette paysage (< 1200px)
             pour laisser la largeur aux données. --}}
        <script>
            (function () {
                var choix = null;
                try {
                    choix = localStorage.getItem('al.sidebar');
                } catch (e) {}
                if (choix === 'collapsed' || (choix === null && window.innerWidth < 1200)) {
                    document.documentElement.classList.add('al-sidebar-collapsed');
                }
            })();
        </script>

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        {{-- select2 : script classique (pas de bundle ESM), chargé après le bundle Vite
             pour que window.jQuery existe déjà — voir resources/js/app.js. --}}
        <script defer src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    </head>
    <body>
        <div class="d-flex">
            {{-- Sidebar (desktop), réductible --}}
            <nav class="al-sidebar al-sidebar-desktop d-none d-lg-flex flex-column" id="sidebar-desktop" aria-label="Navigation principale">
                @include('layouts.partials.sidebar-nav')

                <button type="button" class="al-sidebar-toggle" id="btn-toggle-sidebar" aria-controls="sidebar-desktop" aria-expanded="true" aria-label="Réduire le menu" title="Réduire le menu">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
            </nav>

            {{-- Sidebar (mobile, offcanvas) --}}
            <div class="offcanvas offcanvas-start al-sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
                <span id="sidebarOffcanvasLabel" class="visually-hidden">Menu</span>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" style="z-index: 2;" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
                <div class="offcanvas-body p-0 d-flex flex-column">
                    @include('layouts.partials.sidebar-nav')
                </div>
            </div>

            <div class="flex-grow-1 d-flex flex-column min-vh-100" style="min-width: 0;">
                {{-- Topbar --}}
                <header class="al-topbar d-flex align-items-center justify-content-between gap-2 px-3 sticky-top">
                    <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                        <button class="al-topbar-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Ouvrir le menu">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        @isset($header)
                            <h1 class="al-topbar-title mb-0">{{ $header }}</h1>
                        @endisset
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        <button type="button" class="al-topbar-btn position-relative" title="Notifications" aria-label="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            @if ($notificationsNonLues > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .6rem; margin-left: -.6rem; margin-top: .55rem;">
                                    {{ $notificationsNonLues }}
                                </span>
                            @endif
                        </button>

                        <div class="dropdown">
                            <button class="al-topbar-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mon compte">
                                <span class="al-avatar">{{ $initialesUtilisateur }}</span>
                                <span class="d-none d-md-inline fw-semibold small text-truncate" style="max-width: 160px;">{{ Auth::user()->name }}</span>
                                <i class="bi bi-chevron-down small d-none d-md-inline"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="px-3 py-2 d-md-none">
                                    <div class="fw-semibold small">{{ Auth::user()->name }}</div>
                                </li>
                                <li class="d-md-none"><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Mon profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                {{-- Page content --}}
                <main class="flex-grow-1 p-3 p-lg-4">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
