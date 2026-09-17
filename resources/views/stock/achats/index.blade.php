<x-app-layout>
    <x-slot name="header">Achats</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\Achat::class)
            <button type="button" class="btn btn-primary" id="btn-nouvel-achat">
                <i class="bi bi-plus-lg me-1"></i>Nouvel achat
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-achats">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Fournisseur</th>
                        <th>Total</th>
                        <th>Payé</th>
                        <th>Restant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale nouvel achat --}}
    <div class="modal fade" id="modal-achat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-achat">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvel achat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fournisseur</label>
                                <select name="fournisseur_id" class="form-select select2-fournisseur" required>
                                    <option value=""></option>
                                    @foreach ($fournisseurs as $fournisseur)
                                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Référence (BC)</label>
                                <input type="text" name="reference" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_achat" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <hr>

                        <div id="lignes-achat"></div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-ajouter-ligne">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter une ligne
                        </button>

                        <hr>

                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total</span>
                                    <strong id="achat-total">0 FCFA</strong>
                                </div>
                                <label class="form-label">Montant déjà payé</label>
                                <input type="number" name="montant_paye" id="achat-montant-paye" class="form-control" min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'achat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Gabarit d'une ligne d'achat --}}
    <template id="gabarit-ligne-achat">
        <div class="row align-items-end ligne-achat mb-2">
            <div class="col-6">
                <label class="form-label small">Article</label>
                <select name="lignes[__index__][article_id]" class="form-select select2-article" required>
                    <option value=""></option>
                    @foreach ($articles as $article)
                        <option value="{{ $article->id }}" data-prix="{{ $article->prix_achat }}">{{ $article->reference }} — {{ $article->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-2">
                <label class="form-label small">Quantité</label>
                <input type="number" name="lignes[__index__][quantite]" class="form-control ligne-quantite" min="1" value="1" required>
            </div>
            <div class="col-3">
                <label class="form-label small">Prix unitaire</label>
                <input type="number" name="lignes[__index__][prix_unitaire]" class="form-control ligne-prix" min="0" step="0.01" required>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-outline-danger btn-supprimer-ligne"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>

    @push('scripts')
        <script>
        $(function () {
            let ligneIndex = 0;
            const modalAchat = new bootstrap.Modal('#modal-achat');

            $('.select2-fournisseur').select2({ dropdownParent: $('#modal-achat'), width: '100%' });

            function recalculerTotal() {
                let total = 0;
                $('.ligne-achat').each(function () {
                    const q = parseFloat($(this).find('.ligne-quantite').val()) || 0;
                    const p = parseFloat($(this).find('.ligne-prix').val()) || 0;
                    total += q * p;
                });
                $('#achat-total').text(total.toLocaleString('fr-FR') + ' FCFA');
            }

            function ajouterLigne() {
                const html = $('#gabarit-ligne-achat').html().replaceAll('__index__', ligneIndex++);
                const $ligne = $(html);
                $('#lignes-achat').append($ligne);
                $ligne.find('.select2-article').select2({ dropdownParent: $('#modal-achat'), width: '100%' });
            }

            $('#btn-ajouter-ligne').on('click', ajouterLigne);

            $('#lignes-achat').on('change', '.select2-article', function () {
                const prix = $(this).find(':selected').data('prix');
                if (prix !== undefined) {
                    $(this).closest('.ligne-achat').find('.ligne-prix').val(prix);
                }
                recalculerTotal();
            });

            $('#lignes-achat').on('input', '.ligne-quantite, .ligne-prix', recalculerTotal);

            $('#lignes-achat').on('click', '.btn-supprimer-ligne', function () {
                $(this).closest('.ligne-achat').remove();
                recalculerTotal();
            });

            $('#btn-nouvel-achat').on('click', function () {
                $('#form-achat')[0].reset();
                $('#lignes-achat').empty();
                ligneIndex = 0;
                $('.select2-fournisseur').val('').trigger('change');
                ajouterLigne();
                recalculerTotal();
                modalAchat.show();
            });

            $('#form-achat').on('submit', function (e) {
                e.preventDefault();

                if ($('.ligne-achat').length === 0) {
                    Swal.fire({ icon: 'warning', text: 'Ajoutez au moins une ligne.' });
                    return;
                }

                $.post('/stock/achats', $(this).serialize())
                    .done(function (res) {
                        modalAchat.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-achats').DataTable().ajax.reload();
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });

            $('#table-achats').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('stock.achats.data') }}',
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_achat', name: 'date_achat' },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                    { data: 'fournisseur_nom', name: 'fournisseur_nom' },
                    { data: 'montant_total', name: 'montant_total' },
                    { data: 'montant_paye', name: 'montant_paye' },
                    { data: 'montant_restant', name: 'montant_restant' },
                    { data: 'statut_badge', name: 'statut_paiement', orderable: false },
                ],
                order: [[0, 'desc']],
            });
        });
        </script>
    @endpush
</x-app-layout>
