@php
    $identite = $parametresParGroupe->get('identite_application', collect());
    $nomApplication = $identite->firstWhere('cle', 'application.nom');
    $logoApplication = $identite->firstWhere('cle', 'application.logo');

    $statutJournalier = $parametresParGroupe->get('statut_journalier', collect());
    $heureDebut = $statutJournalier->firstWhere('cle', 'flotte.statut_journalier.heure_debut_fenetre');
    $heureFin = $statutJournalier->firstWhere('cle', 'flotte.statut_journalier.heure_fin_fenetre');
@endphp

<x-app-layout>
    <x-slot name="header">Paramètres</x-slot>

    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i>Réglages globaux de l'application. Effet immédiat, sans déploiement.
    </p>

    <div class="row g-3">
        {{-- Bloc : Identité de l'application --}}
        @if ($nomApplication || $logoApplication)
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h2 class="h6 mb-0"><i class="bi bi-badge-ad me-1"></i>Identité de l'application</h2>
                    </div>
                    <div class="card-body">
                        @if ($logoApplication)
                            <div class="mb-3">
                                <label class="form-label small text-muted mb-1">{{ $logoApplication->libelle }}</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center border rounded bg-light" style="width: 64px; height: 64px;">
                                        @if ($logoApplication->valeur)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoApplication->valeur) }}" alt="Logo" id="apercu-logo" class="img-fluid" style="max-width: 100%; max-height: 100%;">
                                        @else
                                            <i class="bi bi-image text-muted fs-4" id="apercu-logo-placeholder"></i>
                                            <img src="" alt="Logo" id="apercu-logo" class="img-fluid d-none" style="max-width: 100%; max-height: 100%;">
                                        @endif
                                    </div>
                                    @can('parametres.gerer')
                                        <form id="form-logo" class="flex-grow-1">
                                            <input type="file" name="logo" id="input-logo" class="form-control form-control-sm" accept="image/*">
                                            <div class="invalid-feedback d-block small"></div>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endif

                        @if ($nomApplication)
                            <div>
                                <label class="form-label small text-muted mb-1">{{ $nomApplication->libelle }}</label>
                                <form class="form-parametre d-flex gap-2" data-id="{{ $nomApplication->id }}">
                                    <input type="text" name="valeur" class="form-control form-control-sm" value="{{ $nomApplication->valeur }}"
                                           @can('parametres.gerer') @else disabled @endcan>
                                    @can('parametres.gerer')
                                        <button type="submit" class="btn btn-sm btn-primary flex-shrink-0">Enregistrer</button>
                                    @endcan
                                </form>
                                <div class="invalid-feedback d-block small"></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Bloc : Statut journalier des véhicules --}}
        @if ($heureDebut && $heureFin)
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h2 class="h6 mb-0"><i class="bi bi-clock-history me-1"></i>Statut journalier des véhicules</h2>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Fenêtre pendant laquelle un gestionnaire peut ajuster le statut de ses véhicules. Hors fenêtre, seuls admin/superadmin peuvent encore le faire.</p>
                        <form id="form-fenetre-statut" class="row g-2 align-items-end">
                            <input type="hidden" name="id_debut" value="{{ $heureDebut->id }}">
                            <input type="hidden" name="id_fin" value="{{ $heureFin->id }}">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">{{ $heureDebut->libelle }}</label>
                                <input type="time" name="heure_debut" class="form-control form-control-sm" value="{{ $heureDebut->valeur }}"
                                       @can('parametres.gerer') @else disabled @endcan>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">{{ $heureFin->libelle }}</label>
                                <input type="time" name="heure_fin" class="form-control form-control-sm" value="{{ $heureFin->valeur }}"
                                       @can('parametres.gerer') @else disabled @endcan>
                            </div>
                            @can('parametres.gerer')
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                </div>
                            @endcan
                        </form>
                        <div class="invalid-feedback d-block small"></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bloc : Sauvegarde et restauration (à venir) --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-header bg-white border-0 pt-3">
                    <h2 class="h6 mb-0"><i class="bi bi-cloud-arrow-down me-1"></i>Sauvegarde et restauration</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-0">Bientôt disponible.</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.form-parametre').on('submit', function (e) {
                e.preventDefault();

                const $form = $(this);
                const id = $form.data('id');
                $form.find('.form-control').removeClass('is-invalid');
                $form.siblings('.invalid-feedback').text('');

                $.ajax({
                    url: `/admin/parametres/${id}`,
                    method: 'PUT',
                    data: $form.serialize(),
                })
                    .done(function (res) {
                        Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.errors?.valeur?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                        $form.find('.form-control').addClass('is-invalid');
                        $form.siblings('.invalid-feedback').text(msg);
                    });
            });

            $('#form-fenetre-statut').on('submit', function (e) {
                e.preventDefault();

                const $form = $(this);
                $form.find('.form-control').removeClass('is-invalid');
                $form.siblings('.invalid-feedback').text('');

                const idDebut = $form.find('[name=id_debut]').val();
                const idFin = $form.find('[name=id_fin]').val();
                const heureDebut = $form.find('[name=heure_debut]').val();
                const heureFin = $form.find('[name=heure_fin]').val();

                $.when(
                    $.ajax({ url: `/admin/parametres/${idDebut}`, method: 'PUT', data: { valeur: heureDebut } }),
                    $.ajax({ url: `/admin/parametres/${idFin}`, method: 'PUT', data: { valeur: heureFin } })
                )
                    .done(function () {
                        Swal.fire({ icon: 'success', text: 'Fenêtre horaire mise à jour.', timer: 1500, showConfirmButton: false });
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.errors?.valeur?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                        $form.find('.form-control').addClass('is-invalid');
                        $form.siblings('.invalid-feedback').text(msg);
                    });
            });

            $('#input-logo').on('change', function () {
                const fichier = this.files[0];
                if (!fichier) {
                    return;
                }

                const $form = $('#form-logo');
                $form.find('.form-control').removeClass('is-invalid');
                $form.siblings('.invalid-feedback').text('');

                const donnees = new FormData();
                donnees.append('logo', fichier);

                $.ajax({
                    url: '{{ route('admin.parametres.logo') }}',
                    method: 'POST',
                    data: donnees,
                    processData: false,
                    contentType: false,
                })
                    .done(function (res) {
                        $('#apercu-logo').attr('src', res.url).removeClass('d-none');
                        $('#apercu-logo-placeholder').addClass('d-none');
                        Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.errors?.logo?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                        $('#input-logo').addClass('is-invalid');
                        $form.siblings('.invalid-feedback').text(msg);
                    });
            });
        });
        </script>
    @endpush
</x-app-layout>
