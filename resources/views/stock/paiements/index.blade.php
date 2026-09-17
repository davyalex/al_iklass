<x-app-layout>
    <x-slot name="header">Paiements fournisseurs</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\PaiementFournisseur::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-paiement" @disabled($achatsEnCredit->isEmpty())>
                <i class="bi bi-plus-lg me-1"></i>Nouveau paiement
            </button>
        @endcan
    </div>

    @if ($achatsEnCredit->isEmpty())
        <p class="text-muted small">Aucun achat avec un solde restant à payer pour le moment.</p>
    @endif

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-paiements">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Fournisseur</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Référence</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-paiement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-paiement">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau paiement fournisseur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Achat</label>
                            <select name="achat_id" id="paiement-achat" class="form-select select2-achat" required>
                                <option value=""></option>
                                @foreach ($achatsEnCredit as $achat)
                                    <option value="{{ $achat->id }}" data-restant="{{ $achat->montant_restant }}">
                                        {{ $achat->fournisseur_nom }} — reste {{ number_format($achat->montant_restant, 0, ',', ' ') }} FCFA ({{ $achat->date_achat->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text" id="paiement-restant-info"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant (FCFA)</label>
                            <input type="number" name="montant" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode de paiement</label>
                            <select name="mode_paiement_id" class="form-select" required>
                                <option value=""></option>
                                @foreach ($modesPaiement as $mode)
                                    <option value="{{ $mode->id }}">{{ $mode->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_paiement" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Référence</label>
                                <input type="text" name="reference" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        $(function () {
            const modal = new bootstrap.Modal('#modal-paiement');

            $('.select2-achat').select2({ dropdownParent: $('#modal-paiement'), width: '100%' });

            $('#paiement-achat').on('change', function () {
                const restant = $(this).find(':selected').data('restant');
                if (restant !== undefined) {
                    $('#paiement-restant-info').text('Solde restant : ' + Number(restant).toLocaleString('fr-FR') + ' FCFA');
                    $('#form-paiement [name=montant]').attr('max', restant);
                } else {
                    $('#paiement-restant-info').text('');
                }
            });

            $('#btn-nouveau-paiement').on('click', function () {
                $('#form-paiement')[0].reset();
                $('.select2-achat').val('').trigger('change');
                $('#paiement-restant-info').text('');
                modal.show();
            });

            $('#form-paiement').on('submit', function (e) {
                e.preventDefault();

                $.post('/stock/paiements', $(this).serialize())
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

            $('#table-paiements').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('stock.paiements.data') }}',
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_paiement', name: 'date_paiement' },
                    { data: 'fournisseur_nom', name: 'fournisseur_nom' },
                    { data: 'montant', name: 'montant' },
                    { data: 'mode_paiement_libelle', name: 'mode_paiement_libelle', orderable: false },
                    { data: 'reference', name: 'reference', defaultContent: '—' },
                ],
                order: [[0, 'desc']],
            });
        });
        </script>
    @endpush
</x-app-layout>
