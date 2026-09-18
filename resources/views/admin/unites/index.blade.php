<x-app-layout>
    <x-slot name="header">Unités</x-slot>

    <div class="d-flex justify-content-end mb-3">
        @can('create', \App\Models\Unite::class)
            <button type="button" class="btn btn-primary" id="btn-nouvelle-unite">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle unité
            </button>
        @endcan
    </div>

    <div class="row g-3">
        @forelse ($unites as $unite)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm border-0 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ $unite->libelle }}</span>
                            @unless ($unite->actif)
                                <span class="badge bg-secondary">Inactive</span>
                            @endunless
                        </div>
                        <p class="small text-muted mb-3">
                            <i class="bi bi-box-seam me-1"></i>{{ $unite->articles_count }} article(s)
                        </p>

                        <div class="d-flex gap-2">
                            @can('update', $unite)
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill btn-modifier-unite" data-id="{{ $unite->id }}" data-libelle="{{ $unite->libelle }}" data-actif="{{ $unite->actif ? 1 : 0 }}">
                                    <i class="bi bi-pencil me-1"></i>Modifier
                                </button>
                            @endcan
                            @can('delete', $unite)
                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-unite" data-id="{{ $unite->id }}" data-libelle="{{ $unite->libelle }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-muted">Aucune unité pour le moment.</p>
            </div>
        @endforelse
    </div>

    {{-- Modale création / édition --}}
    <div class="modal fade" id="modal-unite" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-unite" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="unite-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-unite-titre">Nouvelle unité</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Libellé</label>
                            <input type="text" name="libelle" class="form-control" placeholder="ex: pièce, litre, kg..." required>
                            <div class="invalid-feedback">Le libellé est obligatoire.</div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="actif" id="unite-actif" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="unite-actif">Active</label>
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
            const modal = new bootstrap.Modal('#modal-unite');
            const $form = $('#form-unite');

            function resetValidation() {
                $form.removeClass('was-validated');
                $form.find('.is-invalid').removeClass('is-invalid');
            }

            $('#btn-nouvelle-unite').on('click', function () {
                $form[0].reset();
                resetValidation();
                $('#unite-id').val('');
                $('#modal-unite-titre').text('Nouvelle unité');
                modal.show();
            });

            $('.btn-modifier-unite').on('click', function () {
                resetValidation();
                $('#unite-id').val($(this).data('id'));
                $form.find('[name=libelle]').val($(this).data('libelle'));
                $('#unite-actif').prop('checked', $(this).data('actif') == 1);
                $('#modal-unite-titre').text("Modifier l'unité");
                modal.show();
            });

            $('.btn-supprimer-unite').on('click', function () {
                const id = $(this).data('id');
                const libelle = $(this).data('libelle');

                Swal.fire({
                    icon: 'warning',
                    text: `Archiver l'unité « ${libelle} » ?`,
                    showCancelButton: true,
                    confirmButtonText: 'Archiver',
                    cancelButtonText: 'Annuler',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.post(`/admin/unites/${id}`, { _method: 'DELETE' })
                        .done(function (res) {
                            Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.reload());
                        })
                        .fail(function (xhr) {
                            Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'Une erreur est survenue.' });
                        });
                });
            });

            $form.on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const formEl = $form[0];
                $form.find('.is-invalid').removeClass('is-invalid');

                if (!formEl.checkValidity()) {
                    $form.addClass('was-validated');
                    return;
                }

                const id = $('#unite-id').val();
                const url = id ? `/admin/unites/${id}` : '/admin/unites';
                const data = $form.serializeArray();
                if (id) {
                    data.push({ name: '_method', value: 'PUT' });
                }
                if (!$('#unite-actif').is(':checked')) {
                    data.push({ name: 'actif', value: '0' });
                }

                $.post(url, $.param(data))
                    .done(function (res) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                const $input = $form.find(`[name="${field}"]`);
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(messages[0]);
                            });
                        }
                        const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                        Swal.fire({ icon: 'error', text: msg });
                    });
            });
        });
        </script>
    @endpush
</x-app-layout>
