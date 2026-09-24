{{-- Bouton icône « réinitialiser les filtres », placé dans la carte de filtre.
     Sans `href` : bouton JS, masqué tant que `visible` est faux ; la page
     l'affiche/masque ensuite selon que des filtres sont actifs. Avec `href` :
     lien de retour à la liste non filtrée, à n'inclure que lorsque la requête
     porte un filtre. --}}
@props(['href' => null, 'visible' => false])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('btn btn-sm btn-outline-secondary al-btn-icon al-btn-reset') }} title="Réinitialiser les filtres" aria-label="Réinitialiser les filtres">
        <i class="bi bi-arrow-counterclockwise"></i>
    </a>
@else
    <button type="button" {{ $attributes->class(['btn btn-sm btn-outline-secondary al-btn-icon al-btn-reset', 'd-none' => ! $visible]) }} title="Réinitialiser les filtres" aria-label="Réinitialiser les filtres">
        <i class="bi bi-arrow-counterclockwise"></i>
    </button>
@endif
