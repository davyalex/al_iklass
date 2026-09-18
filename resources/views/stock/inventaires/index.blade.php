<x-app-layout>
    <x-slot name="header">Inventaires</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\Inventaire::class)
            <button type="button" class="btn btn-primary" id="btn-nouvel-inventaire">
                <i class="bi bi-plus-lg me-1"></i>Nouvel inventaire
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-inventaires" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Catégorie</label>
                    <select name="categorie_id" class="form-select form-select-sm select2-filtre-categorie">
                        <option value="">Toutes</option>
                        @foreach ($categories as $categorie)
                            <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="valide">Validé</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-inventaires">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-inventaires">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="inventaires" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-inventaires">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Catégorie</th>
                        <th>Comptage</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 70px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale nouvel inventaire --}}
    <div class="modal fade" id="modal-inventaire" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-inventaire">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvel inventaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie_id" class="form-select select2-categorie-inventaire">
                                <option value="">Tous les articles</option>
                                @foreach ($categories as $categorie)
                                    <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Laisser sur « Tous les articles » pour un inventaire complet.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control" placeholder="Générée automatiquement si vide">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_inventaire" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer et commencer le comptage</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modale de comptage --}}
    <div class="modal fade" id="modal-comptage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="comptage-reference">—</h5>
                        <span id="comptage-statut"></span>
                        <span class="small text-muted ms-1" id="comptage-categorie"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="comptage-recherche" class="form-control form-control-sm mb-3" placeholder="Rechercher un article...">

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Article</th>
                                    <th class="text-end" style="width: 110px;">Théorique</th>
                                    <th style="width: 130px;">Compté</th>
                                    <th class="text-end" style="width: 90px;">Écart</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                            <tbody id="comptage-lignes"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between flex-wrap gap-2">
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" id="comptage-pdf" href="#"><i class="bi bi-file-earmark-pdf me-2"></i>Télécharger PDF</a></li>
                            <li><a class="dropdown-item" id="comptage-excel" href="#"><i class="bi bi-file-earmark-excel me-2"></i>Télécharger Excel</a></li>
                        </ul>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-danger d-none" id="comptage-supprimer">
                            <i class="bi bi-trash me-1"></i>Supprimer
                        </button>
                        <button type="button" class="btn btn-primary d-none" id="comptage-valider">
                            <i class="bi bi-check2-circle me-1"></i>Valider l'inventaire
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalInventaire = new bootstrap.Modal('#modal-inventaire');
            const modalComptage = new bootstrap.Modal('#modal-comptage');

            const STATUTS = {
                brouillon: { libelle: 'Brouillon', classe: 'bg-secondary' },
                valide: { libelle: 'Validé', classe: 'bg-success' },
            };

            $('.select2-categorie-inventaire').select2({ dropdownParent: $('#modal-inventaire'), width: '100%' });
            $('.select2-filtre-categorie').select2({ width: '100%', placeholder: 'Toutes', selectionCssClass: 'select2-sm' });

            function ligneEcartHtml(theorique, comptee) {
                if (comptee === null || comptee === '' || comptee === undefined) {
                    return '<span class="text-muted">—</span>';
                }
                const ecart = parseInt(comptee, 10) - theorique;
                if (ecart === 0) {
                    return '<span class="text-success">0</span>';
                }
                return ecart > 0
                    ? `<span class="text-success">+${ecart}</span>`
                    : `<span class="text-danger">${ecart}</span>`;
            }

            function afficherComptage(inventaire) {
                const statutInfo = STATUTS[inventaire.statut] ?? { libelle: inventaire.statut, classe: 'bg-secondary' };
                const brouillon = inventaire.statut === 'brouillon';

                $('#comptage-reference').text(inventaire.reference ?? ('Inventaire #' + inventaire.id));
                $('#comptage-statut').html(`<span class="badge ${statutInfo.classe}">${statutInfo.libelle}</span>`);
                $('#comptage-categorie').text(inventaire.categorie?.libelle ?? 'Tous les articles');
                $('#comptage-recherche').val('');

                const $lignes = $('#comptage-lignes').empty();
                inventaire.lignes.forEach(function (ligne) {
                    const $tr = $(`
                        <tr data-ligne-id="${ligne.id}" data-theorique="${ligne.quantite_theorique}" data-nom="${(ligne.article_nom + ' ' + ligne.article_reference).toLowerCase()}">
                            <td>${ligne.article_reference}</td>
                            <td>${ligne.article_nom}</td>
                            <td class="text-end">${ligne.quantite_theorique}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm input-quantite-comptee" min="0" value="${ligne.quantite_comptee ?? ''}" ${brouillon ? '' : 'disabled'}>
                            </td>
                            <td class="text-end cell-ecart">${ligneEcartHtml(ligne.quantite_theorique, ligne.quantite_comptee)}</td>
                            <td>
                                <input type="text" class="form-control form-control-sm input-commentaire-ligne" value="${ligne.commentaire ?? ''}" maxlength="255" ${brouillon ? '' : 'disabled'}>
                            </td>
                        </tr>
                    `);
                    $lignes.append($tr);
                });

                $('#comptage-pdf').attr('href', `/stock/inventaires/export/pdf`);
                $('#comptage-excel').attr('href', `/stock/inventaires/export/excel`);
                $('#comptage-valider').data('id', inventaire.id).toggleClass('d-none', !brouillon);
                $('#comptage-supprimer').data('id', inventaire.id).toggleClass('d-none', !brouillon);

                modalComptage.show();
            }

            function ouvrirComptage(id) {
                $.get(`/stock/inventaires/${id}`, afficherComptage);
            }

            $('#comptage-recherche').on('input', function () {
                const q = $(this).val().toLowerCase();
                $('#comptage-lignes tr').each(function () {
                    $(this).toggle($(this).data('nom').includes(q));
                });
            });

            $('#comptage-lignes').on('change', '.input-quantite-comptee, .input-commentaire-ligne', function () {
                const $row = $(this).closest('tr');
                const ligneId = $row.data('ligne-id');
                const theorique = parseInt($row.data('theorique'), 10);
                const quantite = $row.find('.input-quantite-comptee').val();
                const commentaire = $row.find('.input-commentaire-ligne').val();

                $.ajax({
                    url: `/stock/inventaires/lignes/${ligneId}`,
                    method: 'PUT',
                    data: { quantite_comptee: quantite, commentaire: commentaire },
                })
                    .done(function () {
                        $row.find('.cell-ecart').html(ligneEcartHtml(theorique, quantite === '' ? null : quantite));
                    })
                    .fail(function (xhr) {
                        Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                    });
            });

            $('#btn-nouvel-inventaire').on('click', function () {
                $('#form-inventaire')[0].reset();
                $('.select2-categorie-inventaire').val('').trigger('change');
                modalInventaire.show();
            });

            $('#form-inventaire').on('submit', function (e) {
                e.preventDefault();

                $.post('/stock/inventaires', $(this).serialize())
                    .done(function (res) {
                        modalInventaire.hide();
                        $('#table-inventaires').DataTable().ajax.reload();
                        afficherComptage(res.inventaire);
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            $('#comptage-valider').on('click', function () {
                const id = $(this).data('id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Valider cet inventaire ?',
                    text: 'Les écarts constatés seront appliqués au stock immédiatement. Cette action est définitive.',
                    showCancelButton: true,
                    confirmButtonText: 'Valider',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/stock/inventaires/${id}/valider`)
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                            $('#table-inventaires').DataTable().ajax.reload();
                            afficherComptage(res.inventaire);
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $('#comptage-supprimer').on('click', function () {
                const id = $(this).data('id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Supprimer cet inventaire ?',
                    text: 'Cette action est définitive.',
                    showCancelButton: true,
                    confirmButtonText: 'Supprimer',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({ url: `/stock/inventaires/${id}`, method: 'DELETE' })
                        .done(function (res) {
                            modalComptage.hide();
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                            $('#table-inventaires').DataTable().ajax.reload();
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $('#table-inventaires').on('click', '.btn-detail-inventaire', function () {
                ouvrirComptage($(this).data('id'));
            });

            function filtresInventaires() {
                return {
                    date_debut: $('#filtres-inventaires [name=date_debut]').val(),
                    date_fin: $('#filtres-inventaires [name=date_fin]').val(),
                    categorie_id: $('#filtres-inventaires [name=categorie_id]').val(),
                    statut: $('#filtres-inventaires [name=statut]').val(),
                };
            }

            const tableInventaires = $('#table-inventaires').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.inventaires.data') }}',
                    data: (d) => Object.assign(d, filtresInventaires()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_inventaire', name: 'date_inventaire' },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'categorie_libelle', name: 'categorie.libelle', orderable: false },
                    { data: 'avancement', name: 'avancement', orderable: false },
                    { data: 'statut_badge', name: 'statut', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (inventaire) => `<button type="button" class="btn btn-sm btn-outline-primary btn-detail-inventaire" data-id="${inventaire.id}" title="Détail"><i class="bi bi-eye"></i></button>`,
                    },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-inventaires').on('click', () => tableInventaires.ajax.reload());

            $('#btn-reset-inventaires').on('click', function () {
                $('#filtres-inventaires')[0].reset();
                $('.select2-filtre-categorie').val('').trigger('change');
                tableInventaires.ajax.reload();
            });

            function actualiserBoutonResetInventaires() {
                const actif = Object.values(filtresInventaires()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-inventaires').toggleClass('d-none', !actif);
            }

            $('#filtres-inventaires').on('change input', actualiserBoutonResetInventaires);
            actualiserBoutonResetInventaires();

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresInventaires());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-inventaires').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.inventaires.export.excel') }}');
            });

            $('#btn-export-pdf-inventaires').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.inventaires.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
