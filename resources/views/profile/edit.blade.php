<x-app-layout>
    <x-slot name="header">Mon profil</x-slot>

    <div class="row g-4" style="max-width: 720px;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
