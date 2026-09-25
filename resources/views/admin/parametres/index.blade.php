@inject('identiteApplication', \App\Services\Admin\IdentiteApplicationService::class)
@inject('sauvegardeService', \App\Services\Admin\SauvegardeService::class)
@php
    $identite = $parametresParGroupe->get('identite_application', collect());
    $nomApplication = $identite->firstWhere('cle', 'application.nom');
    $logoApplication = $identite->firstWhere('cle', 'application.logo');

    $statutJournalier = $parametresParGroupe->get('statut_journalier', collect());
    $heureDebut = $statutJournalier->firstWhere('cle', 'flotte.statut_journalier.heure_debut_fenetre');
    $heureFin = $statutJournalier->firstWhere('cle', 'flotte.statut_journalier.heure_fin_fenetre');

    $sauvegarde = $parametresParGroupe->get('sauvegarde', collect());
    $heureSauvegarde = $sauvegarde->firstWhere('cle', 'sauvegarde.heure_execution');
    $retentionSauvegarde = $sauvegarde->firstWhere('cle', 'sauvegarde.retention');
    $dossierSauvegarde = $sauvegarde->firstWhere('cle', 'sauvegarde.dossier_personnalise');
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
                            <div class="mb-4">
                                <label class="form-label small text-muted mb-1" for="input-logo">{{ $logoApplication->libelle }}</label>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div class="d-flex align-items-center justify-content-center border rounded bg-light flex-shrink-0" style="width: 96px; height: 96px;">
                                        <img src="{{ $identiteApplication->logoUrl() ?? '' }}" alt="Logo" id="apercu-logo" @class(['img-fluid', 'd-none' => ! $identiteApplication->aUnLogo()]) style="max-width: 100%; max-height: 100%;">
                                        <i @class(['bi bi-image text-muted fs-3', 'd-none' => $identiteApplication->aUnLogo()]) id="apercu-logo-placeholder"></i>
                                    </div>
                                    @can('parametres.gerer')
                                        <form id="form-logo" class="flex-grow-1" style="min-width: 200px;">
                                            <input type="file" name="logo" id="input-logo" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                                            <div class="invalid-feedback small"></div>
                                            <div class="form-text">PNG à fond transparent recommandé, carré, 512×512 px minimum conseillé (2 Mo max).</div>
                                            <button type="button" id="btn-retirer-logo" @class(['btn btn-sm btn-outline-danger mt-2', 'd-none' => ! $identiteApplication->aUnLogo()])>
                                                <i class="bi bi-trash me-1"></i>Retirer le logo
                                            </button>
                                        </form>
                                    @endcan
                                </div>

                                {{-- Aperçu des déclinaisons générées automatiquement depuis le logo --}}
                                <div class="d-flex align-items-center gap-4 mt-3 p-2 rounded bg-light small text-muted flex-wrap">
                                    <span class="d-flex align-items-center gap-2">
                                        <img src="{{ $identiteApplication->icone('favicon-32.png') }}" alt="" id="apercu-favicon" width="16" height="16">
                                        Favicon (onglet)
                                    </span>
                                    <span class="d-flex align-items-center gap-2">
                                        <img src="{{ $identiteApplication->icone('apple-touch-icon.png') }}" alt="" id="apercu-icone-app" width="36" height="36" class="rounded-3 border">
                                        Icône écran d'accueil (smartphone)
                                    </span>
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

        {{-- Bloc : Sauvegarde et restauration --}}
        @if ($heureSauvegarde && $retentionSauvegarde && $dossierSauvegarde)
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h2 class="h6 mb-0"><i class="bi bi-cloud-arrow-down me-1"></i>Sauvegarde et restauration</h2>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Sauvegarde automatique quotidienne de la base de données (compressée), avec purge des plus anciennes.</p>

                        <form id="form-sauvegarde-parametres" class="row g-2 align-items-end mb-3">
                            <input type="hidden" name="id_heure" value="{{ $heureSauvegarde->id }}">
                            <input type="hidden" name="id_retention" value="{{ $retentionSauvegarde->id }}">
                            <input type="hidden" name="id_dossier" value="{{ $dossierSauvegarde->id }}">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">{{ $heureSauvegarde->libelle }}</label>
                                <input type="time" name="heure" class="form-control form-control-sm" value="{{ $heureSauvegarde->valeur }}"
                                       @can('parametres.gerer') @else disabled @endcan>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">{{ $retentionSauvegarde->libelle }}</label>
                                <input type="number" name="retention" min="1" max="365" class="form-control form-control-sm" value="{{ $retentionSauvegarde->valeur }}"
                                       @can('parametres.gerer') @else disabled @endcan>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Dossier de sauvegarde personnalisé (hors application)</label>
                                <input type="text" name="dossier" class="form-control form-control-sm" placeholder="{{ $sauvegardeService->dossierParDefaut() }}" value="{{ $dossierSauvegarde->valeur }}"
                                       @can('parametres.gerer') @else disabled @endcan>
                                <div class="form-text">Par défaut : <code>{{ $sauvegardeService->dossierParDefaut() }}</code> — laisser vide pour l'utiliser.</div>
                            </div>
                            @can('parametres.gerer')
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                </div>
                            @endcan
                        </form>
                        <div class="invalid-feedback d-block small mb-3"></div>

                        @can('parametres.gerer')
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="btn-sauvegarder-maintenant">
                                <i class="bi bi-cloud-arrow-up me-1"></i>Sauvegarder maintenant
                            </button>
                        @endcan

                        <div id="liste-sauvegardes">
                            @include('admin.parametres.partials.liste-sauvegardes', ['sauvegardes' => $sauvegardes])
                        </div>
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
                        if (res.parametre?.cle === 'application.nom') {
                            $('.js-nom-application').text(res.parametre.valeur);
                        }
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

            $('#form-sauvegarde-parametres').on('submit', function (e) {
                e.preventDefault();

                const $form = $(this);
                $form.find('.form-control').removeClass('is-invalid');
                $form.siblings('.invalid-feedback').text('');

                const idHeure = $form.find('[name=id_heure]').val();
                const idRetention = $form.find('[name=id_retention]').val();
                const idDossier = $form.find('[name=id_dossier]').val();

                $.when(
                    $.ajax({ url: `/admin/parametres/${idHeure}`, method: 'PUT', data: { valeur: $form.find('[name=heure]').val() } }),
                    $.ajax({ url: `/admin/parametres/${idRetention}`, method: 'PUT', data: { valeur: $form.find('[name=retention]').val() } }),
                    $.ajax({ url: `/admin/parametres/${idDossier}`, method: 'PUT', data: { valeur: $form.find('[name=dossier]').val() } })
                )
                    .done(function () {
                        Swal.fire({ icon: 'success', text: 'Réglages de sauvegarde mis à jour.', timer: 1500, showConfirmButton: false });
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.errors?.valeur?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                        $form.find('.form-control').addClass('is-invalid');
                        $form.siblings('.invalid-feedback').text(msg);
                    });
            });

            $('#btn-sauvegarder-maintenant').on('click', function () {
                const $btn = $(this);
                $btn.prop('disabled', true);

                $.post('{{ route('admin.parametres.sauvegardes.creer') }}')
                    .done(function (res) {
                        Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        $btn.prop('disabled', false);
                    });
            });

            $(document).on('click', '.btn-restaurer-sauvegarde', function () {
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'warning',
                    title: 'Restaurer cette sauvegarde ?',
                    html: `Toutes les données actuelles seront <strong>définitivement écrasées</strong> par le contenu de « ${nom} ». Cette action est irréversible.`,
                    showCancelButton: true,
                    confirmButtonText: 'Restaurer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#dc3545',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/admin/parametres/sauvegardes/${nom}/restaurer`)
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message });
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            /**
             * Répercute immédiatement le nouveau logo (ou son retrait) sur la page :
             * aperçus, logo de la sidebar et favicon de l'onglet, sans recharger.
             */
            function appliquerIcones(res) {
                const aUnLogo = !!res.url;
                $('#apercu-logo').attr('src', res.url || '').toggleClass('d-none', !aUnLogo);
                $('#apercu-logo-placeholder').toggleClass('d-none', aUnLogo);
                $('#btn-retirer-logo').toggleClass('d-none', !aUnLogo);
                $('#apercu-favicon').attr('src', res.icones.favicon);
                $('#apercu-icone-app').attr('src', res.icones.apple);
                $('.js-logo-application').attr('src', res.icones.sidebar)
                    .closest('.al-sidebar-logo').toggleClass('al-sidebar-logo-image', aUnLogo);
                $('link[rel="icon"][type="image/svg+xml"]').remove();
                $('link[rel="icon"][sizes="32x32"]').attr('href', res.icones.favicon);
                $('link[rel="icon"][sizes="16x16"]').attr('href', res.icones.favicon);
            }

            $('#input-logo').on('change', function () {
                const fichier = this.files[0];
                if (!fichier) {
                    return;
                }

                const $input = $(this);
                const $erreur = $input.siblings('.invalid-feedback');
                $input.removeClass('is-invalid').prop('disabled', true);
                $erreur.text('');

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
                        appliquerIcones(res);
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.errors?.logo?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                        $input.addClass('is-invalid');
                        $erreur.text(msg);
                    })
                    .always(function () {
                        $input.prop('disabled', false).val('');
                    });
            });

            $('#btn-retirer-logo').on('click', function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'Retirer le logo ?',
                    text: 'Les icônes par défaut seront rétablies partout (connexion, menu, favicon, PDF).',
                    showCancelButton: true,
                    confirmButtonText: 'Retirer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#dc3545',
                }).then(function (choix) {
                    if (!choix.isConfirmed) {
                        return;
                    }

                    $.ajax({ url: '{{ route('admin.parametres.logo.retirer') }}', method: 'DELETE' })
                        .done(function (res) {
                            appliquerIcones(res);
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });
        });
        </script>
    @endpush
</x-app-layout>
