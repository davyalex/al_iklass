@php
    $badgesFinancement = [
        'en_cours' => ['bg-warning text-dark', 'En cours'],
        'solde' => ['bg-success', 'Soldé'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Financements</x-slot>

    <div class="al-page-actions">
        @can('create', \App\Models\Financement::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-financement">
                <i class="bi bi-plus-lg me-1"></i>Déclarer un emprunt
            </button>
        @endcan
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted">Total emprunté (période)</span>
                        <span class="badge rounded-pill bg-primary" id="kpi-total-count" title="Nombre d'emprunts sur la période filtrée">{{ $kpiPeriode['count'] }}</span>
                    </div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-total-montant">{{ \App\Support\Money::format($kpiPeriode['total']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted">Emprunts du mois</span>
                        <span class="badge rounded-pill bg-primary" id="kpi-mois-count" title="Nombre d'emprunts ce mois-ci">{{ $kpiMois['mois_count'] }}</span>
                    </div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-mois-montant">{{ \App\Support\Money::format($kpiMois['mois']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Déjà remboursé (période)</div>
                    <div class="h5 mb-0 text-success" id="kpi-rembourse-montant">{{ \App\Support\Money::format($kpiPeriode['rembourse']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Reste à rembourser (période)</div>
                    <div class="h5 mb-0 text-danger" id="kpi-restant-montant">{{ \App\Support\Money::format($kpiPeriode['restant']) }} FCFA</div>
                </div>
            </div>
        </div>
    </div>
    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i>« Total emprunté », « Déjà remboursé » et « Reste à rembourser » suivent le filtre ci-dessous (toute la période par défaut). Seul « Emprunts du mois » reste fixe sur le mois en cours.
    </p>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-financements" class="row g-2 align-items-end">
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
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="en_cours">En cours</option>
                        <option value="solde">Soldé</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 al-filtres-actions">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-financements">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <x-filtre-reset id="btn-reset-financements" />
                    <div class="ms-auto">
                        <x-export-dropdown id-suffix="financements" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-financements">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Prêteur</th>
                        <th>Total</th>
                        <th>Remboursé</th>
                        <th>Restant</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 80px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale déclarer un emprunt --}}
    <div class="modal fade" id="modal-financement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-financement">
                    <div class="modal-header">
                        <h5 class="modal-title">Déclarer un emprunt</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Prêteur</label>
                            <select name="preteur_id" class="form-select select2-preteur" required>
                                <option value=""></option>
                                @foreach ($preteurs as $preteur)
                                    <option value="{{ $preteur->id }}">{{ $preteur->nom }} ({{ $preteur->type_preteur_libelle }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant emprunté (FCFA)</label>
                            <input type="number" name="montant_total" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_financement" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control" placeholder="Générée automatiquement si vide">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'emprunt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modale détail --}}
    <div class="modal fade" id="modal-detail-financement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="detail-financement-reference">—</h5>
                        <span id="detail-financement-statut"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="small text-muted">Prêteur</div>
                            <div class="fw-semibold" id="detail-financement-preteur">—</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted">Date</div>
                            <div class="fw-semibold" id="detail-financement-date">—</div>
                        </div>
                    </div>
                    <div class="row g-3 text-end mb-3">
                        <div class="col-sm-4">
                            <div class="small text-muted">Total</div>
                            <div class="fw-semibold" id="detail-financement-total">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Remboursé</div>
                            <div class="fw-semibold" id="detail-financement-rembourse">—</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="small text-muted">Restant dû</div>
                            <div class="fw-semibold" id="detail-financement-restant">—</div>
                        </div>
                    </div>
                    <div id="detail-financement-commentaire-bloc" class="d-none mb-3">
                        <div class="small text-muted">Commentaire</div>
                        <div id="detail-financement-commentaire"></div>
                    </div>
                    <div class="small text-muted mb-1">Remboursements</div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Montant</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody id="detail-financement-remboursements"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalFinancement = new bootstrap.Modal('#modal-financement');
            const modalDetailFinancement = new bootstrap.Modal('#modal-detail-financement');

            const STATUTS_FINANCEMENT = {
                en_cours: { libelle: 'En cours', classe: 'bg-warning text-dark' },
                solde: { libelle: 'Soldé', classe: 'bg-success' },
            };

            $('.select2-preteur').select2({ dropdownParent: $('#modal-financement'), width: '100%' });
            $('.select2-filtre-preteur').select2({ width: '100%', placeholder: 'Tous', containerCssClass: 'select2-sm' });

            $('#btn-nouveau-financement').on('click', function () {
                $('#form-financement')[0].reset();
                $('.select2-preteur').val('').trigger('change');
                modalFinancement.show();
            });

            $('#form-financement').on('submit', function (e) {
                e.preventDefault();

                $.post('{{ route('financements.financements.store') }}', $(this).serialize())
                    .done(function (res) {
                        modalFinancement.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-financements').DataTable().ajax.reload();
                        rafraichirKpisPeriode();
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            function filtresFinancements() {
                return {
                    date_debut: $('#filtres-financements [name=date_debut]').val(),
                    date_fin: $('#filtres-financements [name=date_fin]').val(),
                    preteur_id: $('#filtres-financements [name=preteur_id]').val(),
                    statut: $('#filtres-financements [name=statut]').val(),
                };
            }

            function actualiserBoutonResetFinancements() {
                const actif = Object.values(filtresFinancements()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-financements').toggleClass('d-none', !actif);
            }

            $('#filtres-financements').on('change input', actualiserBoutonResetFinancements);
            actualiserBoutonResetFinancements();

            const tableFinancements = $('#table-financements').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('financements.financements.data') }}',
                    data: (d) => Object.assign(d, filtresFinancements()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_financement', name: 'date_financement' },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'preteur_nom', name: 'preteur_nom' },
                    { data: 'montant_total', name: 'montant_total' },
                    { data: 'montant_rembourse', name: 'montant_rembourse' },
                    { data: 'montant_restant', name: 'montant_restant' },
                    { data: 'statut_badge', name: 'statut', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (f) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-financement" data-id="${f.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[0, 'desc']],
            });

            $('#table-financements').on('click', '.btn-detail-financement', function () {
                const id = $(this).data('id');

                $.get(`/financements/emprunts/${id}`, function (financement) {
                    const statutInfo = STATUTS_FINANCEMENT[financement.statut] ?? { libelle: financement.statut, classe: 'bg-secondary' };

                    $('#detail-financement-reference').text(financement.reference ?? ('Financement #' + financement.id));
                    $('#detail-financement-statut').html(`<span class="badge ${statutInfo.classe}">${statutInfo.libelle}</span>`);
                    $('#detail-financement-preteur').text(financement.preteur_nom);
                    $('#detail-financement-date').text(new Date(financement.date_financement).toLocaleDateString('fr-FR'));
                    $('#detail-financement-total').text(formatMontant(financement.montant_total) + ' FCFA');
                    $('#detail-financement-rembourse').text(formatMontant(financement.montant_rembourse) + ' FCFA');
                    $('#detail-financement-restant').text(formatMontant(financement.montant_restant) + ' FCFA');

                    if (financement.commentaire) {
                        $('#detail-financement-commentaire-bloc').removeClass('d-none');
                        $('#detail-financement-commentaire').text(financement.commentaire);
                    } else {
                        $('#detail-financement-commentaire-bloc').addClass('d-none');
                    }

                    const $remboursements = $('#detail-financement-remboursements').empty();
                    if (financement.remboursements.length === 0) {
                        $remboursements.append('<tr><td colspan="3" class="text-center text-muted py-2">Aucun remboursement.</td></tr>');
                    } else {
                        financement.remboursements.forEach(function (r) {
                            $remboursements.append(`
                                <tr>
                                    <td>${new Date(r.date_remboursement).toLocaleDateString('fr-FR')}</td>
                                    <td class="text-end">${formatMontant(r.montant)} FCFA</td>
                                    <td>${r.mode_paiement?.libelle ?? '—'}</td>
                                </tr>
                            `);
                        });
                    }

                    modalDetailFinancement.show();
                });
            });

            function rafraichirKpisPeriode() {
                $.get('{{ route('financements.financements.kpis') }}', filtresFinancements(), function (kpis) {
                    $('#kpi-total-count').text(kpis.count);
                    $('#kpi-total-montant').text(formatMontant(kpis.total) + ' FCFA');
                    $('#kpi-rembourse-montant').text(formatMontant(kpis.rembourse) + ' FCFA');
                    $('#kpi-restant-montant').text(formatMontant(kpis.restant) + ' FCFA');
                    $('#kpi-mois-count').text(kpis.mois_count);
                    $('#kpi-mois-montant').text(formatMontant(kpis.mois) + ' FCFA');
                });
            }

            $('#btn-filtrer-financements').on('click', function () {
                tableFinancements.ajax.reload();
                rafraichirKpisPeriode();
            });

            $('#btn-reset-financements').on('click', function () {
                $('#filtres-financements')[0].reset();
                $('.select2-filtre-preteur').val('').trigger('change');
                tableFinancements.ajax.reload();
                rafraichirKpisPeriode();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresFinancements());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-financements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('financements.financements.export.excel') }}');
            });

            $('#btn-export-pdf-financements').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('financements.financements.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
