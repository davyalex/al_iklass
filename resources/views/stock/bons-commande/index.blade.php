<x-app-layout>
    <x-slot name="header">Bons de commande</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\BonCommande::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-bon-commande">
                <i class="bi bi-plus-lg me-1"></i>Nouveau bon de commande
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-bons-commande">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Fournisseur</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale nouveau bon de commande --}}
    <div class="modal fade" id="modal-bon-commande" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-bon-commande">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau bon de commande</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <select name="fournisseur_id" class="form-select select2-fournisseur-bc" required>
                                    <option value=""></option>
                                    @foreach ($fournisseurs as $fournisseur)
                                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_commande" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <hr>

                        <div id="lignes-bon-commande"></div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-ajouter-ligne-bc">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter une ligne
                        </button>

                        <hr>

                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total estimé</span>
                                    <strong id="bc-total">0 FCFA</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le bon de commande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Gabarit d'une ligne de bon de commande --}}
    <template id="gabarit-ligne-bc">
        <div class="row align-items-end ligne-bc mb-2">
            <div class="col-6">
                <label class="form-label small">Article</label>
                <select name="lignes[__index__][article_id]" class="form-select select2-article-bc" required>
                    <option value=""></option>
                    @foreach ($articles as $article)
                        <option value="{{ $article->id }}" data-prix="{{ $article->prix_achat }}">{{ $article->reference }} — {{ $article->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-2">
                <label class="form-label small">Quantité</label>
                <input type="number" name="lignes[__index__][quantite_commandee]" class="form-control ligne-bc-quantite" min="1" value="1" required>
            </div>
            <div class="col-3">
                <label class="form-label small">Prix unitaire estimé</label>
                <input type="number" name="lignes[__index__][prix_unitaire_estime]" class="form-control ligne-bc-prix" min="0" step="0.01" required>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-outline-danger btn-supprimer-ligne-bc"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            let ligneIndex = 0;
            const modalBc = new bootstrap.Modal('#modal-bon-commande');

            $('.select2-fournisseur-bc').select2({ dropdownParent: $('#modal-bon-commande'), width: '100%' });

            function recalculerTotalBc() {
                let total = 0;
                $('.ligne-bc').each(function () {
                    const q = parseFloat($(this).find('.ligne-bc-quantite').val()) || 0;
                    const p = parseFloat($(this).find('.ligne-bc-prix').val()) || 0;
                    total += q * p;
                });
                $('#bc-total').text(total.toLocaleString('fr-FR') + ' FCFA');
            }

            function ajouterLigneBc() {
                const html = $('#gabarit-ligne-bc').html().replaceAll('__index__', ligneIndex++);
                const $ligne = $(html);
                $('#lignes-bon-commande').append($ligne);
                $ligne.find('.select2-article-bc').select2({ dropdownParent: $('#modal-bon-commande'), width: '100%' });
            }

            $('#btn-ajouter-ligne-bc').on('click', ajouterLigneBc);

            $('#lignes-bon-commande').on('change', '.select2-article-bc', function () {
                const prix = $(this).find(':selected').data('prix');
                if (prix !== undefined) {
                    $(this).closest('.ligne-bc').find('.ligne-bc-prix').val(prix);
                }
                recalculerTotalBc();
            });

            $('#lignes-bon-commande').on('input', '.ligne-bc-quantite, .ligne-bc-prix', recalculerTotalBc);

            $('#lignes-bon-commande').on('click', '.btn-supprimer-ligne-bc', function () {
                $(this).closest('.ligne-bc').remove();
                recalculerTotalBc();
            });

            $('#btn-nouveau-bon-commande').on('click', function () {
                $('#form-bon-commande')[0].reset();
                $('#lignes-bon-commande').empty();
                ligneIndex = 0;
                $('.select2-fournisseur-bc').val('').trigger('change');
                ajouterLigneBc();
                recalculerTotalBc();
                modalBc.show();
            });

            $('#form-bon-commande').on('submit', function (e) {
                e.preventDefault();

                if ($('.ligne-bc').length === 0) {
                    Swal.fire({ icon: 'warning', text: 'Ajoutez au moins une ligne.' });
                    return;
                }

                $.post('/stock/bons-commande', $(this).serialize())
                    .done(function (res) {
                        modalBc.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-bons-commande').DataTable().ajax.reload();
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            $('#table-bons-commande').on('click', '.btn-annuler-bc', function () {
                const id = $(this).data('id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Annuler ce bon de commande ?',
                    showCancelButton: true,
                    confirmButtonText: 'Annuler le bon',
                    cancelButtonText: 'Retour',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.post(`/stock/bons-commande/${id}/annuler`)
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                            $('#table-bons-commande').DataTable().ajax.reload();
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $('#table-bons-commande').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('stock.bons-commande.data') }}',
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_commande', name: 'date_commande' },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'fournisseur_nom', name: 'fournisseur_nom' },
                    { data: 'statut_badge', name: 'statut', orderable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (bc) {
                            let boutons = `<a href="/stock/achats?bon_commande_id=${bc.id}" class="btn btn-sm btn-outline-primary">Recevoir</a>`;
                            if (bc.statut === 'en_attente') {
                                boutons += ` <button type="button" class="btn btn-sm btn-outline-danger btn-annuler-bc" data-id="${bc.id}">Annuler</button>`;
                            }
                            return boutons;
                        },
                    },
                ],
                order: [[0, 'desc']],
            });
        });
        </script>
    @endpush
</x-app-layout>
