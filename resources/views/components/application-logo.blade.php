{{-- Logo + nom de l'application tels que paramétrés (Administration > Paramètres),
     avec repli sur l'icône par défaut. `compact` : pastille seule, sans le nom. --}}
@props(['compact' => false])

@inject('identiteApplication', \App\Services\Admin\IdentiteApplicationService::class)

<div {{ $attributes->class(['al-app-logo', 'al-app-logo-compact' => $compact]) }}>
    @if ($identiteApplication->aUnLogo())
        <img src="{{ $identiteApplication->logoUrl() }}" alt="{{ $identiteApplication->nom() }}" class="al-app-logo-image">
    @else
        <img src="{{ asset('icones/favicon.svg') }}" alt="" class="al-app-logo-defaut" aria-hidden="true">
    @endif
    @unless ($compact)
        <span class="al-app-logo-nom">{{ $identiteApplication->nom() }}</span>
    @endunless
</div>
