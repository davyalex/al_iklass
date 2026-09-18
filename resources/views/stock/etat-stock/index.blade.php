<x-app-layout>
    <x-slot name="header">État de stock</x-slot>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Valeur totale du stock</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($kpis['valeur_stock']) }} FCFA</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Articles en alerte</div>
                    <div class="h5 mb-0 {{ $kpis['en_alerte'] > 0 ? 'text-danger' : '' }}">{{ $kpis['en_alerte'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Total pièces disponibles</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);">{{ $kpis['total_pieces'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white mb-3">
        <div class="card-body">
            <form id="filtres-etat-stock" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Article</label>
                    <select name="article_id" class="form-select form-select-sm select2-filtre-article">
                        <option value="">Tous</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}">{{ $article->reference }} — {{ $article->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1 d-none d-md-block">&nbsp;</label>
                    <div class="form-check form-switch mb-0 pt-md-1">
                        <input class="form-check-input" type="checkbox" role="switch" name="en_alerte" id="filtre-en-alerte-etat">
                        <label class="form-check-label" for="filtre-en-alerte-etat">En alerte</label>
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-etat-stock">
                        Réinitialiser
                    </button>
                    <div class="ms-md-auto">
                        <x-export-dropdown id-suffix="etat-stock" />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-etat-stock">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Unité</th>
                        <th>Stock</th>
                        <th>Seuil</th>
                        <th>Coût moyen d'achat</th>
                        <th>Statut</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-article').select2({ width: '100%', placeholder: 'Tous' });

            function filtresEtatStock() {
                return {
                    article_id: $('#filtres-etat-stock [name=article_id]').val(),
                    en_alerte: $('#filtres-etat-stock [name=en_alerte]').is(':checked') ? 1 : '',
                };
            }

            function actualiserBoutonResetEtatStock() {
                const actif = Object.values(filtresEtatStock()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-etat-stock').toggleClass('d-none', !actif);
            }

            $('#filtres-etat-stock').on('change input', actualiserBoutonResetEtatStock);
            actualiserBoutonResetEtatStock();

            const tableEtatStock = $('#table-etat-stock').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('stock.etat-stock.data') }}',
                    data: (d) => Object.assign(d, filtresEtatStock()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'reference', name: 'reference' },
                    { data: 'nom', name: 'nom' },
                    { data: 'categorie_libelle', name: 'categorie.libelle', orderable: false },
                    { data: 'unite_libelle', name: 'unite.libelle', orderable: false },
                    { data: 'quantite_stock', name: 'quantite_stock' },
                    { data: 'seuil_alerte', name: 'seuil_alerte' },
                    { data: 'prix_achat', name: 'prix_achat' },
                    { data: 'statut_badge', name: 'actif', orderable: false },
                ],
                order: [[1, 'asc']],
                createdRow: function (row, data) {
                    if (data.en_alerte) {
                        $(row).addClass('table-danger');
                    }
                },
            });

            $('.select2-filtre-article').on('change', () => tableEtatStock.ajax.reload());
            $('#filtre-en-alerte-etat').on('change', () => tableEtatStock.ajax.reload());

            $('#btn-reset-etat-stock').on('click', function () {
                $('#filtres-etat-stock')[0].reset();
                $('.select2-filtre-article').val('').trigger('change');
                tableEtatStock.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresEtatStock());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-etat-stock').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.etat-stock.export.excel') }}');
            });

            $('#btn-export-pdf-etat-stock').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('stock.etat-stock.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
