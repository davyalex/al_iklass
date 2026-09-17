@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2 text-decoration-none']) }}>
    <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
          style="width: {{ $compact ? '32px' : '48px' }}; height: {{ $compact ? '32px' : '48px' }}; background-color: var(--al-navy); color: #fff;">
        <i class="bi bi-truck" style="font-size: {{ $compact ? '1rem' : '1.4rem' }};"></i>
    </span>
    @unless ($compact)
        <span class="fw-bold" style="color: var(--al-navy); font-size: 1.25rem;">AL-IKLASS</span>
    @endunless
</div>
