<x-app-layout>
    <x-slot name="header">Fournisseurs</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\Fournisseur::class)
            <button type="button" class="btn btn-primary" id="btn-nouveau-fournisseur">
                <i class="bi bi-plus-lg me-1"></i>Nouveau fournisseur
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Achats</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fournisseurs as $fournisseur)
                        <tr>
                            <td>{{ $fournisseur->nom }}</td>
                            <td>{{ $fournisseur->telephone ?? '—' }}</td>
                            <td>{{ $fournisseur->email ?? '—' }}</td>
                            <td>{{ $fournisseur->achats_count }}</td>
                            <td>
                                @if ($fournisseur->actif)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('stock.fournisseurs.compte', $fournisseur) }}" class="btn btn-sm btn-outline-primary" title="Voir détail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $fournisseur)
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-modifier-fournisseur" data-id="{{ $fournisseur->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun fournisseur pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $fournisseurs->links() }}</div>

    <div class="modal fade" id="modal-fournisseur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-fournisseur">
                    <input type="hidden" name="id" id="fournisseur-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-fournisseur-titre">Nouveau fournisseur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="adresse" class="form-control">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="actif" id="fournisseur-actif" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="fournisseur-actif">Actif</label>
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

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal('#modal-fournisseur');

            $('#btn-nouveau-fournisseur').on('click', function () {
                $('#form-fournisseur')[0].reset();
                $('#fournisseur-id').val('');
                $('#modal-fournisseur-titre').text('Nouveau fournisseur');
                modal.show();
            });

            $('.btn-modifier-fournisseur').on('click', function () {
                const id = $(this).data('id');
                $.get(`/stock/fournisseurs/${id}`, function (f) {
                    $('#fournisseur-id').val(f.id);
                    $('#form-fournisseur [name=nom]').val(f.nom);
                    $('#form-fournisseur [name=telephone]').val(f.telephone);
                    $('#form-fournisseur [name=email]').val(f.email);
                    $('#form-fournisseur [name=adresse]').val(f.adresse);
                    $('#fournisseur-actif').prop('checked', !!f.actif);
                    $('#modal-fournisseur-titre').text('Modifier le fournisseur');
                    modal.show();
                });
            });

            $('#form-fournisseur').on('submit', function (e) {
                e.preventDefault();

                const id = $('#fournisseur-id').val();
                const url = id ? `/stock/fournisseurs/${id}` : '/stock/fournisseurs';
                const data = $(this).serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }
                if (!$('#fournisseur-actif').is(':checked')) {
                    data.push({ name: 'actif', value: '0' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });
        });
        </script>
    @endpush
</x-app-layout>
