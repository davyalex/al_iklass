<x-app-layout>
    <x-slot name="header">Tableau de bord</x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h5 mb-2" style="color: var(--al-navy);">
                Bonjour, {{ Auth::user()->name }}
            </h1>
            <p class="text-muted mb-0">
                Bienvenue sur AL-IKLASS. Les modules (stock, véhicules, recettes, caisses…) apparaîtront ici au fur et à mesure de leur mise en service.
            </p>
        </div>
    </div>
</x-app-layout>
