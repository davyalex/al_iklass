<x-app-layout>
    <x-slot name="header">Remboursements</x-slot>

    <div class="al-page-actions">
        @can('create', \App\Models\RemboursementFinancement::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-remboursement" @disabled($financementsEnCours->isEmpty())>
                <i class="bi bi-plus-lg me-1"></i>Nouveau remboursement
            </button>
        @endcan
    </div>

    @if ($financementsEnCours->isEmpty())
        <p class="text-muted small">Aucun emprunt avec un solde restant à rembourser pour le moment.</p>
    @endif

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-remboursements" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Prêteur</label>
                    <select name="preteur_id" class="form-select form-select-sm select2-filtre-preteur">
                        <option value="">Tous</option>
                        @foreach ($preteurs as $preteur)
                            <option value="{{ $preteur->id }}">{{ $preteur->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Mode</label>
                    <select name="mode_paiement_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($modesPaiement as $mode)
                            <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 al-filtres-actions">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-remboursements">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <x-filtre-reset id="btn-reset-remboursements" />
                    <div class="ms-auto">
                        <x-export-dropdown id-suffix="remboursements" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-remboursements">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Prêteur</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Référence</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-remboursement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-remboursement">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau remboursement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Prêteur</label>
                            <select id="remboursement-preteur" class="form-select select2-preteur-remboursement" required>
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Emprunt</label>
                            <select name="financement_id" id="remboursement-financement" class="form-select select2-financement" required disabled>
                                <option value=""></option>
                                @foreach ($financementsEnCours as $financement)
                                    <option value="{{ $financement->id }}" data-restant="{{ $financement->montant_restant }}" data-preteur-id="{{ $financement->preteur_id }}" data-preteur-nom="{{ $financement->preteur_nom }}">
                                        {{ $financement->reference ?? ('Financement #'.$financement->id) }} — {{ $financement->date_financement->format('d/m/Y') }} — reste {{ \App\Support\Money::format($financement->montant_restant) }} FCFA
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text" id="remboursement-financement-aide">Choisissez d'abord un prêteur.</div>
                            <div class="form-text" id="remboursement-restant-info"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Type de remboursement</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type_remboursement" id="remboursement-type-total" checked>
                                <label class="btn btn-outline-primary" for="remboursement-type-total">Remboursement total</label>
                                <input type="radio" class="btn-check" name="type_remboursement" id="remboursement-type-partiel">
                                <label class="btn btn-outline-primary" for="remboursement-type-partiel">Remboursement partiel</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant (FCFA)</label>
                            <input type="number" name="montant" id="remboursement-montant" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode de paiement</label>
                            <select name="mode_paiement_id" class="form-select">
                                <option value=""></option>
                                @foreach ($modesPaiement as $mode)
                                    <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_remboursement" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le remboursement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal('#modal-remboursement');

            // Un financement n'apparaît dans la liste que si son prêteur est celui
            // choisi au-dessus : évite de devoir reconnaître le bon emprunt parmi
            // plusieurs lignes qui répètent le même nom de prêteur.
            function matcherFinancementParPreteur(params, data) {
                if (!data.id) {
                    return data;
                }
                const preteurId = $('#remboursement-preteur').val();
                if (preteurId && String($(data.element).data('preteurId')) !== String(preteurId)) {
                    return null;
                }
                const terme = $.trim(params.term || '');
                if (terme === '' || data.text.toUpperCase().indexOf(terme.toUpperCase()) > -1) {
                    return data;
                }
                return null;
            }

            $('.select2-financement').select2({ dropdownParent: $('#modal-remboursement'), width: '100%', matcher: matcherFinancementParPreteur });
            $('.select2-preteur-remboursement').select2({ dropdownParent: $('#modal-remboursement'), width: '100%' });
            $('.select2-filtre-preteur').select2({ width: '100%', placeholder: 'Tous', containerCssClass: 'select2-sm' });

            // Liste des prêteurs ayant au moins un emprunt en cours, construite une
            // fois à partir des options de financement déjà rendues côté serveur.
            const preteursAvecEmpruntEnCours = new Map();
            $('#remboursement-financement option[value!=""]').each(function () {
                const id = $(this).data('preteurId');
                const nom = $(this).data('preteurNom');
                if (id && !preteursAvecEmpruntEnCours.has(id)) {
                    preteursAvecEmpruntEnCours.set(id, nom);
                }
            });
            Array.from(preteursAvecEmpruntEnCours.entries())
                .sort((a, b) => a[1].localeCompare(b[1], 'fr'))
                .forEach(([id, nom]) => $('#remboursement-preteur').append(`<option value="${id}">${nom}</option>`));

            $('#remboursement-preteur').on('change', function () {
                const preteurId = $(this).val();
                $('#remboursement-financement').val('').trigger('change');

                if (!preteurId) {
                    $('#remboursement-financement').prop('disabled', true);
                    $('#remboursement-financement-aide').text("Choisissez d'abord un prêteur.");
                    return;
                }

                $('#remboursement-financement').prop('disabled', false);

                const financementsDuPreteur = $('#remboursement-financement option').filter(function () {
                    return String($(this).data('preteurId')) === String(preteurId);
                });
                $('#remboursement-financement-aide').text(
                    financementsDuPreteur.length === 1 ? '1 emprunt en cours pour ce prêteur.' : financementsDuPreteur.length + ' emprunts en cours pour ce prêteur.'
                );

                if (financementsDuPreteur.length === 1) {
                    $('#remboursement-financement').val(financementsDuPreteur.first().val()).trigger('change');
                }
            });

            // Bascule Remboursement total / partiel : en "total", le montant est
            // verrouillé sur le solde restant de l'emprunt choisi ; en "partiel", le
            // champ se vide et devient modifiable (plafonné à max).
            function appliquerTypeRemboursement() {
                const restant = parseFloat($('#remboursement-financement').find(':selected').data('restant'));

                if ($('#remboursement-type-total').is(':checked')) {
                    $('#remboursement-montant').val(Number.isFinite(restant) ? restant : '').prop('readonly', true);
                } else {
                    $('#remboursement-montant').val('').prop('readonly', false).trigger('focus');
                }
            }

            $('#modal-remboursement input[name=type_remboursement]').on('change', appliquerTypeRemboursement);

            $('#remboursement-financement').on('change', function () {
                const restant = $(this).find(':selected').data('restant');
                if (restant !== undefined) {
                    $('#remboursement-restant-info').text('Solde restant : ' + Number(restant).toLocaleString('fr-FR') + ' FCFA');
                    $('#form-remboursement [name=montant]').attr('max', restant);
                } else {
                    $('#remboursement-restant-info').text('');
                }
                appliquerTypeRemboursement();
            });

            $('#btn-nouveau-remboursement').on('click', function () {
                $('#form-remboursement')[0].reset();
                $('.select2-preteur-remboursement').val('').trigger('change');
                $('#remboursement-financement').prop('disabled', true).val('').trigger('change');
                $('#remboursement-financement-aide').text("Choisissez d'abord un prêteur.");
                $('#remboursement-restant-info').text('');
                appliquerTypeRemboursement();
                modal.show();
            });

            $('#form-remboursement').on('submit', function (e) {
                e.preventDefault();

                $.post('{{ route('financements.remboursements.store') }}', $(this).serialize())
                    .done(function (res) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.errors?.montant?.[0] || xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            function filtresRemboursements() {
                return {
                    date_debut: $('#filtres-remboursements [name=date_debut]').val(),
                    date_fin: $('#filtres-remboursements [name=date_fin]').val(),
                    preteur_id: $('#filtres-remboursements [name=preteur_id]').val(),
                    mode_paiement_id: $('#filtres-remboursements [name=mode_paiement_id]').val(),
                };
            }

            function actualiserBoutonResetRemboursements() {
                const actif = Object.values(filtresRemboursements()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-remboursements').toggleClass('d-none', !actif);
            }

            $('#filtres-remboursements').on('change input', actualiserBoutonResetRemboursements);
            actualiserBoutonResetRemboursements();

            const tableRemboursements = $('#table-remboursements').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('financements.remboursements.data') }}',
                    data: (d) => Object.assign(d, filtresRemboursements()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_remboursement', name: 'date_remboursement' },
                    { data: 'preteur_nom', name: 'preteur_nom' },
                    { data: 'montant', name: 'montant' },
                    { data: 'mode_paiement_libelle', name: 'mode_paiement_libelle', orderable: false },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-remboursements').on('click', () => tableRemboursements.ajax.reload());

            $('#btn-reset-remboursements').on('click', function () {
                $('#filtres-remboursements')[0].reset();
                $('.select2-filtre-preteur').val('').trigger('change');
                tableRemboursements.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresRemboursements());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-remboursements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('financements.remboursements.export.excel') }}');
            });

            $('#btn-export-pdf-remboursements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('financements.remboursements.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
