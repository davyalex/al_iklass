{{-- Lien de la sidebar. `data-al-label` alimente l'infobulle affichée quand la
     sidebar est réduite (voir resources/js/app.js). --}}
@props(['href', 'icon', 'active' => false])

<li class="nav-item">
    <a href="{{ $href }}" @class(['nav-link', 'active' => $active]) data-al-label="{{ trim($slot) }}" @if ($active) aria-current="page" @endif>
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
        <span class="al-nav-label">{{ $slot }}</span>
    </a>
</li>
