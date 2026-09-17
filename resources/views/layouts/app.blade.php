<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}@isset($title) — {{ $title }}@endisset</title>

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
        {{-- select2 : script classique (pas de bundle ESM), chargé après le bundle Vite
             pour que window.jQuery existe déjà — voir resources/js/app.js. --}}
        <script defer src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    </head>
    <body>
        <div class="d-flex">
            {{-- Sidebar (desktop) --}}
            <nav class="al-sidebar d-none d-lg-flex flex-column py-2" style="width: 260px; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto;">
                @include('layouts.partials.sidebar-nav')
            </nav>

            {{-- Sidebar (mobile, offcanvas) --}}
            <div class="offcanvas offcanvas-start al-sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
                <div class="offcanvas-header">
                    <span id="sidebarOffcanvasLabel" class="visually-hidden">Menu</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
                </div>
                <div class="offcanvas-body p-0 d-flex flex-column">
                    @include('layouts.partials.sidebar-nav')
                </div>
            </div>

            <div class="flex-grow-1 d-flex flex-column min-vh-100" style="min-width: 0;">
                {{-- Topbar --}}
                <header class="al-topbar d-flex align-items-center justify-content-between px-3 py-2 sticky-top">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                            <i class="bi bi-list fs-4"></i>
                        </button>
                        @isset($header)
                            <div class="fw-semibold fs-5" style="color: var(--al-navy);">{{ $header }}</div>
                        @endisset
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-light position-relative" title="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .6rem;">
                                0
                            </span>
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5"></i>
                                <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Mon profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
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
