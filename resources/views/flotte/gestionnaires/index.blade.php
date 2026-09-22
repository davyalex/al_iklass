@php
    $badgesStatut = \App\Support\StatutVehiculeBadges::classes();
    $fondsKpiStatut = \App\Support\StatutVehiculeBadges::fondsKpi();
@endphp

<x-app-layout>
    <x-slot name="header">Gestionnaires</x-slot>

    @include('flotte.partials.statut-styles')

    {{-- KPIs (identiques à la page Véhicules) --}}
    <div class="row g-3 mb-3">
        @foreach ($statuts as $statut)
            <div class="col-6 col-lg">
                <div class="card shadow-sm border-0 {{ $fondsKpiStatut[$statut->code] ?? 'bg-white' }} h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ $statut->libelle }}</div>
                        <div class="h5 mb-0">{{ $kpis['par_statut'][$statut->code] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-6 col-lg">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Recette du jour (en circulation, tous gestionnaires confondus)</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['recette_en_circulation'] }} FCFA</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="card border-0 bg-light mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Gestionnaire</label>
                    <select id="recherche-gestionnaire" class="form-select form-select-sm select2-filtre-gestionnaire">
                        <option value="">Tous les gestionnaires</option>
                        @foreach ($gestionnaires as $gestionnaire)
                            <option value="{{ strtolower($gestionnaire->name) }}">{{ $gestionnaire->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select id="filtre-statut-gestionnaire" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        @foreach ($statuts as $statut)
                            <option value="{{ $statut->code }}">{{ $statut->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Véhicule</label>
                    <select id="filtre-vehicule-gestionnaire" class="form-select form-select-sm select2-filtre-vehicule">
                        <option value="">Tous les véhicules</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ strtolower($vehicule->code) }}">{{ $vehicule->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-filtrer-gestionnaire">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-filtre-gestionnaire" title="Réinitialiser les filtres">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    @can('utilisateurs.gerer')
                        <button type="button" class="btn btn-primary ms-md-auto" id="btn-nouveau-gestionnaire">
                            <i class="bi bi-plus-lg me-1"></i>Nouveau gestionnaire
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Une carte par gestionnaire : stats, actions, et ses véhicules affichés en icônes --}}
    <div id="groupes-gestionnaires">
        @forelse ($gestionnaires as $gestionnaire)
            @php
                $vehiculesEnCirculation = $gestionnaire->vehiculesAttribues->filter(fn ($v) => $v->statut?->code === 'en_circulation');
                $comptesParStatutGestionnaire = $gestionnaire->vehiculesAttribues->groupBy(fn ($v) => $v->statut?->code);
                $attenduDuJour = (float) $vehiculesEnCirculation->sum('recette_journaliere');
                $dejaVerseAujourdhui = (float) ($versementsDuJour[$gestionnaire->id] ?? 0);
                $resteAVerser = max(0, $attenduDuJour - $dejaVerseAujourdhui);
            @endphp
            <div class="card shadow-sm border-0 bg-white mb-3 groupe-gestionnaire {{ $resteAVerser > 0 || (float) $gestionnaire->dette > 0 ? 'border-start border-4 border-danger' : '' }}"
                 data-nom="{{ strtolower($gestionnaire->name) }}" data-attente="{{ $resteAVerser > 0 ? 1 : 0 }}" data-dette="{{ (float) $gestionnaire->dette > 0 ? 1 : 0 }}">
                <div class="card-header bg-white border-0 pt-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <i class="bi bi-person-badge text-muted"></i>
                        <h2 class="h6 mb-0">{{ $gestionnaire->name }}</h2>
                        <span class="badge bg-light text-dark border">{{ $gestionnaire->vehiculesAttribues->count() }} véhicule(s)</span>
                        @if ($resteAVerser > 0)
                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Versement en attente</span>
                        @else
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>À jour</span>
                        @endif
                        @if ((float) $gestionnaire->dette > 0)
                            <span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i>Dette</span>
                        @endif
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-dark btn-detail-gestionnaire" data-id="{{ $gestionnaire->id }}" title="Détail du compte">
                                <i class="bi bi-eye"></i>
                            </button>
                            @can('flotte.vehicule.gerer')
                                <button type="button" class="btn btn-sm btn-success btn-verser" data-id="{{ $gestionnaire->id }}" data-nom="{{ $gestionnaire->name }}" data-suggestion="{{ $resteAVerser }}"
                                        data-vehicules="{{ $gestionnaire->vehiculesAttribues->map->only(['id', 'code'])->toJson() }}">
                                    <i class="bi bi-cash-coin me-1"></i>Verser
                                </button>
                            @endcan
                            @can('update', $gestionnaire)
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-gestionnaire" data-id="{{ $gestionnaire->id }}" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endcan
                            @can('resetPassword', $gestionnaire)
                                <button type="button" class="btn btn-sm btn-outline-primary btn-reset-gestionnaire" data-id="{{ $gestionnaire->id }}" data-nom="{{ $gestionnaire->name }}" title="Réinitialiser le mot de passe">
                                    <i class="bi bi-key"></i>
                                </button>
                            @endcan
                            @can('delete', $gestionnaire)
                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-gestionnaire" data-id="{{ $gestionnaire->id }}" data-nom="{{ $gestionnaire->name }}" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>

                    {{-- Légende des statuts véhicule — style volontairement neutre (pastilles), distinct des badges de versement ci-dessus --}}
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="small text-muted">Véhicules :</span>
                        @foreach ($statuts as $statut)
                            <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                <span class="rounded-circle {{ $badgesStatut[$statut->code] ?? 'bg-secondary' }}" style="width: 8px; height: 8px;"></span>
                                {{ $statut->libelle }} : {{ $comptesParStatutGestionnaire->get($statut->code, collect())->count() }}
                            </span>
                        @endforeach
                    </div>

                    {{-- Mini KPI versement du jour --}}
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted">Attendu</div>
                                <div class="fw-semibold small">{{ \App\Support\Money::format($attenduDuJour) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-2 text-center bg-success-subtle">
                                <div class="small text-muted">Déjà versé</div>
                                <div class="fw-semibold small">{{ \App\Support\Money::format($dejaVerseAujourdhui) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-2 text-center {{ $resteAVerser > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                <div class="small text-muted">Reste à verser</div>
                                <div class="fw-semibold small">{{ \App\Support\Money::format($resteAVerser) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-2 text-center {{ (float) $gestionnaire->dette > 0 ? 'bg-danger-subtle' : '' }}" title="Solde reporté des jours précédents">
                                <div class="small text-muted">Solde dû</div>
                                <div class="fw-semibold small">{{ \App\Support\Money::format($gestionnaire->dette) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    @if ($gestionnaire->vehiculesAttribues->isEmpty())
                        <p class="small text-muted mb-0 message-vide">Aucun véhicule attribué.</p>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($gestionnaire->vehiculesAttribues as $vehicule)
                                <button type="button"
                                        class="btn btn-outline-secondary chip-vehicule-gestion statut-{{ $vehicule->statut?->code }} position-relative d-flex flex-column align-items-center gap-1 py-2 px-1 btn-voir-vehicule"
                                        data-id="{{ $vehicule->id }}" data-statut="{{ $vehicule->statut?->code }}"
                                        data-code="{{ strtolower($vehicule->code) }}"
                                        title="{{ $vehicule->statut?->libelle ?? 'Sans statut' }}">
                                    <span class="position-absolute top-0 start-100 translate-middle border border-2 border-white rounded-circle point-statut statut-{{ $vehicule->statut?->code }} {{ $badgesStatut[$vehicule->statut?->code] ?? 'bg-secondary' }}"></span>
                                    <i class="bi bi-truck-front fs-4"></i>
                                    <span class="small fw-semibold text-truncate" style="max-width: 100%;">{{ $vehicule->code }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted">Aucun gestionnaire pour le moment.</p>
        @endforelse
    </div>

    <x-flotte.vehicule-modals :statuts="$statuts" :gestionnaires="$gestionnaires" />

    @can('flotte.vehicule.gerer')
        {{-- Modale enregistrement d'un versement --}}
        <div class="modal fade" id="modal-versement" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-versement" class="needs-validation" novalidate>
                        <input type="hidden" name="gestionnaire_id" id="versement-gestionnaire-id">
                        <div class="modal-header">
                            <h5 class="modal-title">Verser — <span id="versement-gestionnaire-nom"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Véhicule concerné (facultatif)</label>
                                <select name="vehicule_id" id="versement-vehicule" class="form-select">
                                    <option value="">Non spécifié (versement global)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Montant (FCFA)</label>
                                <input type="number" name="montant" id="versement-montant" class="form-control" min="0.01" step="0.01" required>
                                <div class="invalid-feedback">Le montant doit être positif.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mode de paiement</label>
                                <select name="mode_paiement_id" class="form-select" required>
                                    <option value=""></option>
                                    @foreach ($modesPaiement as $mode)
                                        <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Le mode de paiement est obligatoire.</div>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date_versement" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Référence</label>
                                    <input type="text" name="reference" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Commentaire (facultatif)</label>
                                <input type="text" name="commentaire" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">Enregistrer le versement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modale compte gestionnaire : infos, KPI de versements, historique filtrable --}}
    <div class="modal fade" id="modal-compte-gestionnaire" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compte — <span id="compte-gestionnaire-nom"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3 small">
                        <dt class="col-4">Nom d'utilisateur</dt><dd class="col-8" id="compte-username"></dd>
                        <dt class="col-4">Téléphone</dt><dd class="col-8" id="compte-telephone"></dd>
                        <dt class="col-4">Email</dt><dd class="col-8" id="compte-email"></dd>
                    </dl>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted">Versé (total)</div>
                                <div class="fw-semibold small" id="compte-kpi-total"></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted">Nb versements</div>
                                <div class="fw-semibold small" id="compte-kpi-nombre"></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 text-center bg-danger-subtle">
                                <div class="small text-muted">Solde dû</div>
                                <div class="fw-semibold small" id="compte-kpi-solde"></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="small text-uppercase text-muted">Versements récents</h6>
                    <div id="compte-historique-liste" class="small mb-3">
                        <p class="text-muted">Chargement…</p>
                    </div>

                    <h6 class="small text-uppercase text-muted">Historique de la dette</h6>
                    <div id="compte-historique-dette-liste" class="small">
                        <p class="text-muted">Chargement…</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="lien-historique-complet" class="btn btn-outline-primary me-auto">
                        <i class="bi bi-clock-history me-1"></i>Voir tout l'historique
                    </a>
                    @can('flotte.dette.gerer')
                        <button type="button" class="btn btn-outline-danger" id="btn-annuler-dette">
                            <i class="bi bi-x-circle me-1"></i>Annuler la dette
                        </button>
                    @endcan
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @can('flotte.dette.gerer')
        {{-- Modale annulation de dette (totale ou partielle) --}}
        <div class="modal fade" id="modal-annuler-dette" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-annuler-dette" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title">Annuler la dette — <span id="annuler-dette-nom"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label d-block">Type d'annulation</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type_annulation_dette" id="ad-type-total" checked>
                                    <label class="btn btn-outline-danger" for="ad-type-total">Totale</label>
                                    <input type="radio" class="btn-check" name="type_annulation_dette" id="ad-type-partiel">
                                    <label class="btn btn-outline-danger" for="ad-type-partiel">Partielle</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Montant (FCFA)</label>
                                <input type="number" name="montant" id="annuler-dette-montant" class="form-control" min="0.01" step="0.01" required>
                                <div class="invalid-feedback">Le montant doit être positif et ne peut pas dépasser la dette actuelle.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Motif</label>
                                <textarea name="motif" class="form-control" rows="3" required maxlength="500"></textarea>
                                <div class="invalid-feedback">Le motif est obligatoire.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger">Confirmer l'annulation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('utilisateurs.gerer')
        {{-- Modale création / édition gestionnaire (réutilise admin.users.store/update, rôle imposé) --}}
        <div class="modal fade" id="modal-gestionnaire" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="form-gestionnaire" class="needs-validation" novalidate>
                        <input type="hidden" name="id" id="gestionnaire-id">
                        <input type="hidden" name="role" value="gestionnaire">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modal-gestionnaire-titre">Nouveau gestionnaire</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nom et prénom</label>
                                <input type="text" name="name" class="form-control" required>
                                <div class="invalid-feedback">Le nom est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom d'utilisateur</label>
                                <input type="text" name="username" class="form-control" required>
                                <div class="invalid-feedback">Le nom d'utilisateur est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" required>
                                <div class="invalid-feedback">Le téléphone doit contenir 10 chiffres.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email (facultatif)</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="alert alert-info small mb-0" id="gestionnaire-info-mdp">
                                Un mot de passe à 5 chiffres sera généré automatiquement à la création.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-gestionnaire').select2({ width: '100%', placeholder: 'Tous les gestionnaires', selectionCssClass: 'select2-sm' });
            $('.select2-filtre-vehicule').select2({ width: '100%', placeholder: 'Tous les véhicules', selectionCssClass: 'select2-sm' });

            const modalGestionnaireEl = document.getElementById('modal-gestionnaire');
            const modalGestionnaire = modalGestionnaireEl ? new bootstrap.Modal(modalGestionnaireEl) : null;
            const $formGestionnaire = $('#form-gestionnaire');

            const modalVersementEl = document.getElementById('modal-versement');
            const modalVersement = modalVersementEl ? new bootstrap.Modal(modalVersementEl) : null;
            const $formVersement = $('#form-versement');

            $('#groupes-gestionnaires').on('click', '.btn-verser', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');
                const suggestion = parseFloat($(this).data('suggestion')) || 0;
                const vehicules = $(this).data('vehicules') || [];

                $formVersement[0].reset();
                $formVersement.removeClass('was-validated');
                $formVersement.find('.is-invalid').removeClass('is-invalid');
                $('#versement-gestionnaire-id').val(id);
                $('#versement-gestionnaire-nom').text(nom);
                $('#versement-montant').val(suggestion > 0 ? suggestion.toFixed(2) : '');

                const $selectVehicule = $('#versement-vehicule');
                $selectVehicule.find('option:not(:first)').remove();
                vehicules.forEach(function (vehicule) {
                    $selectVehicule.append(`<option value="${vehicule.id}">${vehicule.code}</option>`);
                });

                modalVersement.show();
            });

            $formVersement.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const formEl = $formVersement[0];
                $formVersement.find('.is-invalid').removeClass('is-invalid');

                if (!formEl.checkValidity()) {
                    $formVersement.addClass('was-validated');
                    return;
                }

                $.post('{{ route('flotte.versements.store') }}', $formVersement.serialize())
                    .done(function (res) {
                        modalVersement.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                const $input = $formVersement.find(`[name="${field}"]`);
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        }
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            const modalCompteEl = document.getElementById('modal-compte-gestionnaire');
            const modalCompte = modalCompteEl ? new bootstrap.Modal(modalCompteEl) : null;
            let compteGestionnaireId = null;
            let compteGestionnaireNom = null;
            let compteSoldeDuBrut = 0;

            function chargerCompteGestionnaire() {
                $.get(`/flotte/gestionnaires/${compteGestionnaireId}/compte`, function (res) {
                    compteGestionnaireNom = res.gestionnaire.name;
                    compteSoldeDuBrut = res.kpis.solde_du_brut;

                    $('#compte-gestionnaire-nom').text(res.gestionnaire.name);
                    $('#compte-username').text('@' + res.gestionnaire.username);
                    $('#compte-telephone').text(res.gestionnaire.telephone || '—');
                    $('#compte-email').text(res.gestionnaire.email || '—');
                    $('#compte-kpi-total').text(res.kpis.total_tout_temps + ' FCFA');
                    $('#compte-kpi-nombre').text(res.kpis.nombre_versements);
                    $('#compte-kpi-solde').text(res.kpis.solde_du + ' FCFA');
                    $('#btn-annuler-dette').prop('disabled', compteSoldeDuBrut <= 0);

                    if (!res.versements_recents.length) {
                        $('#compte-historique-liste').html('<p class="text-muted mb-0">Aucun versement pour ce gestionnaire.</p>');
                    } else {
                        const lignes = res.versements_recents.map(function (v) {
                            return `<div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                <div>
                                    <strong>${v.montant} FCFA</strong> — ${v.mode_paiement}
                                    ${v.vehicule_code ? ' <span class="badge bg-light text-dark border">' + v.vehicule_code + '</span>' : ''}
                                    ${v.reference ? ' <span class="text-muted">(' + v.reference + ')</span>' : ''}
                                    ${v.commentaire ? '<br><span class="text-muted">' + v.commentaire + '</span>' : ''}
                                </div>
                                <div class="text-muted text-nowrap ms-2">${v.date}</div>
                            </div>`;
                        }).join('');

                        $('#compte-historique-liste').html(lignes);
                    }

                    if (!res.historique_dette.length) {
                        $('#compte-historique-dette-liste').html('<p class="text-muted mb-0">Aucun mouvement de dette pour ce gestionnaire.</p>');
                        return;
                    }

                    const lignesDette = res.historique_dette.map(function (h) {
                        const badge = h.type === 'bascule'
                            ? '<span class="badge bg-danger">Bascule</span>'
                            : '<span class="badge bg-success">Annulation</span>';
                        return `<div class="d-flex justify-content-between align-items-start border-bottom py-2">
                            <div>
                                ${badge} <strong>${h.montant} FCFA</strong>
                                ${h.auteur ? ' <span class="text-muted">— ' + h.auteur + '</span>' : ''}
                                ${h.motif ? '<br><span class="text-muted">' + h.motif + '</span>' : ''}
                            </div>
                            <div class="text-muted text-nowrap ms-2">${h.date}</div>
                        </div>`;
                    }).join('');

                    $('#compte-historique-dette-liste').html(lignesDette);
                });
            }

            $('#groupes-gestionnaires').on('click', '.btn-detail-gestionnaire', function () {
                compteGestionnaireId = $(this).data('id');
                $('#compte-historique-liste').html('<p class="text-muted">Chargement…</p>');
                $('#compte-historique-dette-liste').html('<p class="text-muted">Chargement…</p>');
                $('#lien-historique-complet').attr('href', `{{ route('flotte.versements.index') }}?gestionnaire_id=${compteGestionnaireId}`);
                chargerCompteGestionnaire();
                modalCompte.show();
            });

            const modalAnnulerDetteEl = document.getElementById('modal-annuler-dette');
            const modalAnnulerDette = modalAnnulerDetteEl ? new bootstrap.Modal(modalAnnulerDetteEl) : null;
            const $formAnnulerDette = $('#form-annuler-dette');

            function appliquerTypeAnnulationDette() {
                if ($('#ad-type-total').is(':checked')) {
                    $('#annuler-dette-montant').val(compteSoldeDuBrut > 0 ? compteSoldeDuBrut.toFixed(2) : '').prop('readonly', true);
                } else {
                    $('#annuler-dette-montant').val('').prop('readonly', false).trigger('focus');
                }
            }

            $('#modal-annuler-dette input[name=type_annulation_dette]').on('change', appliquerTypeAnnulationDette);

            $('#btn-annuler-dette').on('click', function () {
                modalCompte.hide();
                $formAnnulerDette[0].reset();
                $formAnnulerDette.removeClass('was-validated');
                $formAnnulerDette.find('.is-invalid').removeClass('is-invalid');
                $('#annuler-dette-nom').text(compteGestionnaireNom);
                $('#ad-type-total').prop('checked', true);
                $('#annuler-dette-montant').attr('max', compteSoldeDuBrut);
                appliquerTypeAnnulationDette();
                modalAnnulerDette.show();
            });

            $formAnnulerDette.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const formEl = $formAnnulerDette[0];
                if (!formEl.checkValidity()) {
                    $formAnnulerDette.addClass('was-validated');
                    return;
                }

                $.post(`/flotte/gestionnaires/${compteGestionnaireId}/dette/annuler`, $formAnnulerDette.serialize())
                    .done(function (res) {
                        modalAnnulerDette.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        chargerCompteGestionnaire();
                        modalCompte.show();
                    })
                    .fail(function (xhr) {
                        const erreurs = xhr.responseJSON?.errors;
                        const msg = erreurs ? Object.values(erreurs).flat()[0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            function resetValidationGestionnaire() {
                $formGestionnaire.removeClass('was-validated');
                $formGestionnaire.find('.is-invalid').removeClass('is-invalid');
            }

            $('#btn-nouveau-gestionnaire').on('click', function () {
                $formGestionnaire[0].reset();
                resetValidationGestionnaire();
                $('#gestionnaire-id').val('');
                $('#modal-gestionnaire-titre').text('Nouveau gestionnaire');
                $('#gestionnaire-info-mdp').show();
                modalGestionnaire.show();
            });

            $('#groupes-gestionnaires').on('click', '.btn-modifier-gestionnaire', function () {
                const id = $(this).data('id');

                $.get(`/admin/users/${id}`, function (utilisateur) {
                    resetValidationGestionnaire();
                    $('#gestionnaire-id').val(utilisateur.id);
                    $('#form-gestionnaire [name=name]').val(utilisateur.name);
                    $('#form-gestionnaire [name=username]').val(utilisateur.username);
                    $('#form-gestionnaire [name=telephone]').val(utilisateur.telephone);
                    $('#form-gestionnaire [name=email]').val(utilisateur.email);
                    $('#modal-gestionnaire-titre').text('Modifier le gestionnaire');
                    $('#gestionnaire-info-mdp').hide();
                    modalGestionnaire.show();
                });
            });

            $formGestionnaire.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const formEl = $formGestionnaire[0];
                $formGestionnaire.find('.is-invalid').removeClass('is-invalid');

                if (!formEl.checkValidity()) {
                    $formGestionnaire.addClass('was-validated');
                    return;
                }

                const id = $('#gestionnaire-id').val();
                const url = id ? `/admin/users/${id}` : '{{ route('admin.users.store') }}';
                const data = $formGestionnaire.serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        modalGestionnaire.hide();
                        if (res.password) {
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                html: `Mot de passe généré : <span class="fs-4 fw-bold font-monospace">${res.password}</span><br><small class="text-muted">Communiquez-le au gestionnaire, il ne sera plus affiché.</small>`,
                                confirmButtonText: 'J\'ai noté le mot de passe',
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        }
                    })
                    .fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                const $input = $formGestionnaire.find(`[name="${field}"]`);
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        }
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            $('#groupes-gestionnaires').on('click', '.btn-reset-gestionnaire', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'question',
                    title: 'Réinitialiser le mot de passe ?',
                    text: `Un nouveau mot de passe sera généré pour ${nom}.`,
                    showCancelButton: true,
                    confirmButtonText: 'Réinitialiser',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/admin/users/${id}/reset-password`)
                        .done(function (res) {
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                html: `Nouveau mot de passe : <span class="fs-4 fw-bold font-monospace">${res.password}</span><br><small class="text-muted">Communiquez-le au gestionnaire, il ne sera plus affiché.</small>`,
                                confirmButtonText: 'J\'ai noté le mot de passe',
                            });
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $('#groupes-gestionnaires').on('click', '.btn-supprimer-gestionnaire', function () {
                const id = $(this).data('id');
                const nom = $(this).data('nom');

                Swal.fire({
                    icon: 'warning',
                    title: 'Supprimer ce gestionnaire ?',
                    text: `${nom} n'apparaîtra plus dans la liste. Ses véhicules resteront enregistrés mais ne seront plus affectés.`,
                    showCancelButton: true,
                    confirmButtonText: 'Supprimer',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({ url: `/admin/users/${id}`, method: 'DELETE' })
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            function appliquerFiltresGestionnaires() {
                const q = $('#recherche-gestionnaire').val().trim().toLowerCase();
                const statutCode = $('#filtre-statut-gestionnaire').val() || '';
                const vehiculeCode = $('#filtre-vehicule-gestionnaire').val() || '';
                const filtreVehiculeActif = !!statutCode || !!vehiculeCode;
                const filtreActif = !!q || filtreVehiculeActif;

                $('.chip-vehicule-gestion').each(function () {
                    const correspondStatut = !statutCode || $(this).data('statut') === statutCode;
                    const correspondVehicule = !vehiculeCode || $(this).data('code') === vehiculeCode;
                    $(this).toggleClass('d-none', !(correspondStatut && correspondVehicule));
                });

                $('.groupe-gestionnaire').each(function () {
                    const $groupe = $(this);
                    const nom = ($groupe.data('nom') || '').toString();
                    const total = $groupe.find('.chip-vehicule-gestion').length;

                    let visible = !q || nom.includes(q);
                    if (visible && filtreVehiculeActif) {
                        visible = total > 0 && $groupe.find('.chip-vehicule-gestion:not(.d-none)').length > 0;
                    }

                    $groupe.find('.message-vide').toggleClass('d-none', filtreVehiculeActif);
                    $groupe.toggleClass('d-none', !visible);
                });

                $('#btn-reset-filtre-gestionnaire').toggleClass('d-none', !filtreActif);
            }

            $('#btn-filtrer-gestionnaire').on('click', appliquerFiltresGestionnaires);

            $('#btn-reset-filtre-gestionnaire').on('click', function () {
                $('#recherche-gestionnaire').val('').trigger('change');
                $('#filtre-statut-gestionnaire').val('');
                $('#filtre-vehicule-gestionnaire').val('').trigger('change');
                appliquerFiltresGestionnaires();
            });
        });
        </script>
    @endpush
</x-app-layout>
