<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.head-identite')

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
    </head>
    <body class="al-guest">
        <div class="d-flex flex-column align-items-center justify-content-center min-vh-100 px-3 py-4">
            <a href="/" class="mb-4 text-decoration-none">
                <x-application-logo />
            </a>

            <div class="card shadow-sm border-0 al-guest-card">
                <div class="card-body p-4 p-sm-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
