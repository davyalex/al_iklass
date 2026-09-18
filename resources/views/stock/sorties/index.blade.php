<x-app-layout>
    <x-slot name="header">Sorties de stock</x-slot>

    <div class="d-flex justify-content-end gap-2 mb-3">
        @can('sortieInterne', \App\Models\MouvementStock::class)
            <button type="button" class="btn btn-outline-primary" id="btn-sortie-interne">
                <i class="bi bi-truck me-1"></i>Sortie interne
            </button>
        @endcan
        @can('sortieVente', \App\Models\MouvementStock::class)
            <button type="button" class="btn btn-primary" id="btn-sortie-externe">
                <i class="bi bi-cash-coin me-1"></i>Vente externe
            </button>
        @endcan
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-sorties" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Article</label>
                    <select name="article_id" class="form-select form-select-sm select2-filtre-article">
                        <option value="">Tous</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}">{{ $article->reference }} — {{ $article->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Nature</label>
                    <select name="nature" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <option value="interne">Interne</option>
                        <option value="externe">Vente externe</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-filtrer-sorties">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-sorties">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="sorties" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-sorties">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Article</th>
                        <th>Nature</th>
                        <th>Quantité</th>
                        <th>Destination</th>
                        <th>Prix de vente</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modale sortie --}}
    <div class="modal fade" id="modal-sortie" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-sortie">
                    <input type="hidden" name="nature" id="sortie-nature">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-sortie-titre">Sortie de stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Article</label>
                            <select name="article_id" class="form-select select2-article-sortie" required>
                                <option value=""></option>
                                @foreach ($articles as $article)
                                    <option value="{{ $article->id }}" data-stock="{{ $article->quantite_stock }}">
                                        {{ $article->reference }} — {{ $article->nom }} (stock : {{ $article->quantite_stock }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantité</label>
                            <input type="number" name="quantite" class="form-control" min="1" value="1" required>
                        </div>

                        <div class="champs-interne">
                            <div class="mb-3">
                                <label class="form-label">Véhicule</label>
                                <select name="vehicule_id" class="form-select select2-vehicule">
                                    <option value=""></option>
                                    @foreach ($vehicules as $vehicule)
                                        <option value="{{ $vehicule->id }}">{{ $vehicule->code }} — {{ $vehicule->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="champs-externe">
                            <div class="mb-3">
                                <label class="form-label">Prix de vente (FCFA, par unité)</label>
                                <input type="number" name="prix_vente" class="form-control" min="0" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Véhicule externe (immatriculation)</label>
                                <input type="text" name="vehicule_externe" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Acheteur</label>
                                <input type="text" name="acheteur" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <input type="text" name="motif" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer la sortie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = new bootstrap.Modal('#modal-sortie');

            function ouvrirModale(nature) {
                $('#form-sortie')[0].reset();
                $('#sortie-nature').val(nature);
                $('.select2-article-sortie, .select2-vehicule').val('').trigger('change');

                if (nature === 'interne') {
                    $('#modal-sortie-titre').text('Sortie interne (véhicule du parc)');
                    $('.champs-interne').show();
                    $('.champs-externe').hide();
                    $('[name=vehicule_id]').prop('required', true);
                    $('[name=prix_vente], [name=vehicule_externe], [name=acheteur]').prop('required', false);
                } else {
                    $('#modal-sortie-titre').text('Vente externe');
                    $('.champs-interne').hide();
                    $('.champs-externe').show();
                    $('[name=vehicule_id]').prop('required', false);
                    $('[name=prix_vente], [name=vehicule_externe], [name=acheteur]').prop('required', true);
                }

                modal.show();
            }

            $('#btn-sortie-interne').on('click', () => ouvrirModale('interne'));
            $('#btn-sortie-externe').on('click', () => ouvrirModale('externe'));

            $('.select2-article-sortie').select2({ dropdownParent: $('#modal-sortie'), width: '100%' });
            $('.select2-vehicule').select2({ dropdownParent: $('#modal-sortie'), width: '100%' });
            $('.select2-filtre-article').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            $('#form-sortie').on('submit', function (e) {
                e.preventDefault();

                $.post('/stock/sorties', $(this).serialize())
                    .done(function (res) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: res.message, timer: 1800, showConfirmButton: false });
                        $('#table-sorties').DataTable().ajax.reload();
                    })
                    .fail(function (xhr) {
                        const data = xhr.responseJSON;
                        if (data?.insufficient_stock) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Stock insuffisant',
                                text: data.message,
                            });
                        } else {
                            const msg = data?.message || 'Une erreur est survenue.';
                            Swal.fire({ icon: 'error', text: msg });
                        }
                    });
            });

            function filtresSorties() {
                return {
                    date_debut: $('#filtres-sorties [name=date_debut]').val(),
                    date_fin: $('#filtres-sorties [name=date_fin]').val(),
                    article_id: $('#filtres-sorties [name=article_id]').val(),
                    nature: $('#filtres-sorties [name=nature]').val(),
                };
            }

            function actualiserBoutonResetSorties() {
                const actif = Object.values(filtresSorties()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-sorties').toggleClass('d-none', !actif);
            }

            $('#filtres-sorties').on('change input', actualiserBoutonResetSorties);
            actualiserBoutonResetSorties();

            const tableSorties = $('#table-sorties').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.sorties.data') }}',
                    data: (d) => Object.assign(d, filtresSorties()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_mouvement', name: 'date_mouvement' },
                    { data: 'article_nom', name: 'article_nom' },
                    { data: 'nature_badge', name: 'nature', orderable: false },
                    { data: 'quantite', name: 'quantite' },
                    { data: 'destination', name: 'destination', orderable: false },
                    { data: 'prix_vente', name: 'prix_vente' },
                ],
                order: [[0, 'desc']],
            });

            $('#btn-filtrer-sorties').on('click', () => tableSorties.ajax.reload());

            $('#btn-reset-sorties').on('click', function () {
                $('#filtres-sorties')[0].reset();
                $('.select2-filtre-article').val('').trigger('change');
                tableSorties.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresSorties());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-sorties').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.sorties.export.excel') }}');
            });

            $('#btn-export-pdf-sorties').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.sorties.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
