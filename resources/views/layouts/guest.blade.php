<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="d-flex flex-column align-items-center justify-content-center min-vh-100 py-4">
            <a href="/" class="mb-4 text-decoration-none d-flex flex-column align-items-center">
                <x-application-logo />
            </a>

            <div class="card shadow-sm border-0" style="width: 100%; max-width: 420px;">
                <div class="card-body p-4 p-sm-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
