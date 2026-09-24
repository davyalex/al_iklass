{{-- Titre, favicons, icône d'écran d'accueil (iOS/Android) et manifeste :
     tous dérivés du nom et du logo paramétrés (Administration > Paramètres). --}}
@inject('identiteApplication', \App\Services\Admin\IdentiteApplicationService::class)

<title>{{ $identiteApplication->nom() }}@isset($title) — {{ $title }}@endisset</title>

<meta name="application-name" content="{{ $identiteApplication->nom() }}">
<meta name="apple-mobile-web-app-title" content="{{ $identiteApplication->nom() }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#073763">

@unless ($identiteApplication->aUnLogo())
    <link rel="icon" type="image/svg+xml" href="{{ asset('icones/favicon.svg') }}">
@endunless
<link rel="icon" type="image/png" sizes="32x32" href="{{ $identiteApplication->icone('favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $identiteApplication->icone('favicon-16.png') }}">
<link rel="shortcut icon" href="{{ $identiteApplication->icone('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $identiteApplication->icone('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ route('manifest') }}">
