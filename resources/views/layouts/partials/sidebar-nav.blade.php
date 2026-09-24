@inject('identiteApplication', \App\Services\Admin\IdentiteApplicationService::class)

<a href="{{ route('dashboard') }}" class="al-sidebar-brand">
    <span @class(['al-sidebar-logo', 'al-sidebar-logo-image' => $identiteApplication->aUnLogo()])>
        <img src="{{ $identiteApplication->aUnLogo() ? $identiteApplication->icone('icone-192.png') : asset('icones/favicon.svg') }}" alt="{{ $identiteApplication->nom() }}" class="js-logo-application">
    </span>
    <span class="al-sidebar-brand-name js-nom-application">{{ $identiteApplication->nom() }}</span>
</a>

<div class="al-sidebar-scroll">
    <ul class="nav flex-column">
        <x-sidebar.link :href="route('dashboard')" icon="bi-speedometer2" :active="request()->routeIs('dashboard')">Tableau de bord</x-sidebar.link>

        @can('stock.tableau_bord.voir')
            <x-sidebar.section>Stock</x-sidebar.section>
            <x-sidebar.link :href="route('stock.articles.index')" icon="bi-box-seam" :active="request()->routeIs('stock.articles.*')">Articles</x-sidebar.link>
            <x-sidebar.link :href="route('stock.etat-stock.index')" icon="bi-clipboard-data" :active="request()->routeIs('stock.etat-stock.*')">Suivi de stock</x-sidebar.link>
            <x-sidebar.link :href="route('stock.mouvements.index')" icon="bi-arrow-left-right" :active="request()->routeIs('stock.mouvements.*')">Mouvements</x-sidebar.link>
            <x-sidebar.link :href="route('stock.fournisseurs.index')" icon="bi-truck-front" :active="request()->routeIs('stock.fournisseurs.*')">Fournisseurs</x-sidebar.link>
            <x-sidebar.link :href="route('stock.bons-commande.index')" icon="bi-file-earmark-text" :active="request()->routeIs('stock.bons-commande.*')">Bons de commande</x-sidebar.link>
            <x-sidebar.link :href="route('stock.achats.index')" icon="bi-cart-check" :active="request()->routeIs('stock.achats.*')">Achats (réceptions)</x-sidebar.link>
            <x-sidebar.link :href="route('stock.paiements.index')" icon="bi-cash-coin" :active="request()->routeIs('stock.paiements.*')">Paiements</x-sidebar.link>
            <x-sidebar.link :href="route('stock.sorties.index')" icon="bi-box-arrow-up-right" :active="request()->routeIs('stock.sorties.*')">Sorties</x-sidebar.link>
            <x-sidebar.link :href="route('stock.inventaires.index')" icon="bi-clipboard-check" :active="request()->routeIs('stock.inventaires.*')">Inventaires</x-sidebar.link>
        @endcan

        @canany(['flotte.vehicule.voir', 'flotte.vehicule.voir_affectes', 'flotte.vehicule.remise_circulation'])
            <x-sidebar.section>Flotte</x-sidebar.section>
            <x-sidebar.link :href="route('flotte.vehicules.index')" icon="bi-truck-front-fill" :active="request()->routeIs('flotte.vehicules.*')">Véhicules</x-sidebar.link>
            <x-sidebar.link :href="route('flotte.etat-parc.index')" icon="bi-calendar-week" :active="request()->routeIs('flotte.etat-parc.*')">État du parc</x-sidebar.link>
            @can('flotte.vehicule.voir')
                <x-sidebar.link :href="route('flotte.gestionnaires.index')" icon="bi-person-badge" :active="request()->routeIs('flotte.gestionnaires.*')">Gestionnaires</x-sidebar.link>
            @endcan
        @endcanany

        @can('operations.voir')
            <x-sidebar.section>Entretien</x-sidebar.section>
            <x-sidebar.link :href="route('flotte.operations.index')" icon="bi-tools" :active="request()->routeIs('flotte.operations.index')">Opérations programmées</x-sidebar.link>
            <x-sidebar.link :href="route('flotte.operations.historique.index')" icon="bi-clock-history" :active="request()->routeIs('flotte.operations.historique.*')">Historique opérations</x-sidebar.link>
        @endcan

        @can('interventions.voir')
            @cannot('operations.voir')
                <x-sidebar.section>Entretien</x-sidebar.section>
            @endcannot
            <x-sidebar.link :href="route('flotte.interventions.index')" icon="bi-exclamation-triangle" :active="request()->routeIs('flotte.interventions.index')">Interventions</x-sidebar.link>
            <x-sidebar.link :href="route('flotte.interventions.historique.index')" icon="bi-clock-history" :active="request()->routeIs('flotte.interventions.historique.*')">Historique interventions</x-sidebar.link>
        @endcan

        {{-- Argent : versements/dette (par gestionnaire ou vue admin) et le registre
        des 4 caisses — regroupés car transverses à Flotte et Stock, indépendamment
        de la section d'où provient chaque mouvement. --}}
        @canany(['flotte.vehicule.voir', 'flotte.versement.gerer', 'flotte.dette.gerer', 'flotte.dette.regler', 'caisse.voir'])
            <x-sidebar.section>Finance</x-sidebar.section>
            @canany(['flotte.vehicule.voir', 'flotte.versement.gerer'])
                <x-sidebar.link :href="route('flotte.versements.index')" icon="bi-cash-coin" :active="request()->routeIs('flotte.versements.*')">Versements</x-sidebar.link>
            @endcanany
            @canany(['flotte.dette.gerer', 'flotte.dette.regler'])
                <x-sidebar.link :href="route('flotte.dettes.index')" icon="bi-exclamation-octagon" :active="request()->routeIs('flotte.dettes.*')">Dette</x-sidebar.link>
            @endcanany
            @can('caisse.voir')
                <x-sidebar.link :href="route('admin.caisses.index')" icon="bi-wallet2" :active="request()->routeIs('admin.caisses.*')">Caisses</x-sidebar.link>
            @endcan
        @endcanany

        {{-- Prêts & financements : emprunts contractés par l'entreprise (banque ou
        personne) et leurs remboursements — domaine à part de la trésorerie
        quotidienne ci-dessus (Finance), porté par admin/gestionnaire de stock, pas
        par les gestionnaires de parc. --}}
        @can('financements.voir')
            <x-sidebar.section>Prêts & financements</x-sidebar.section>
            <x-sidebar.link :href="route('financements.preteurs.index')" icon="bi-bank" :active="request()->routeIs('financements.preteurs.*')">Prêteurs</x-sidebar.link>
            <x-sidebar.link :href="route('financements.financements.index')" icon="bi-cash-stack" :active="request()->routeIs('financements.financements.*')">Financements</x-sidebar.link>
            <x-sidebar.link :href="route('financements.remboursements.index')" icon="bi-arrow-return-left" :active="request()->routeIs('financements.remboursements.*')">Remboursements</x-sidebar.link>
        @endcan

        @canany(['utilisateurs.voir', 'roles.voir', 'unites.voir', 'audit.voir', 'parametres.voir'])
            <x-sidebar.section>Administration</x-sidebar.section>
            @can('utilisateurs.voir')
                <x-sidebar.link :href="route('admin.users.index')" icon="bi-people" :active="request()->routeIs('admin.users.*')">Utilisateurs</x-sidebar.link>
            @endcan
            @can('roles.voir')
                <x-sidebar.link :href="route('admin.roles.index')" icon="bi-shield-lock" :active="request()->routeIs('admin.roles.*')">Rôles</x-sidebar.link>
            @endcan
            @can('unites.voir')
                <x-sidebar.link :href="route('admin.unites.index')" icon="bi-rulers" :active="request()->routeIs('admin.unites.*')">Unités</x-sidebar.link>
            @endcan
            @can('audit.voir')
                <x-sidebar.link :href="route('admin.audit.index')" icon="bi-journal-text" :active="request()->routeIs('admin.audit.*')">Journal d'audit</x-sidebar.link>
            @endcan
            @can('parametres.voir')
                <x-sidebar.link :href="route('admin.parametres.index')" icon="bi-sliders" :active="request()->routeIs('admin.parametres.*')">Paramètres</x-sidebar.link>
            @endcan
        @endcanany
    </ul>
</div>
