<x-app-layout>
    <x-slot name="header">Caisses</x-slot>

    {{-- Solde courant de chaque caisse (entrées - sorties, tout historique confondu) --}}
    <div class="row g-3 mb-3 row-cols-2 row-cols-lg-4">
        @foreach ($caisses as $caisse)
            <div class="col">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ $caisse->libelle }}</div>
                        <div class="h5 mb-0" style="color: var(--al-navy);">{{ \App\Support\Money::format($soldes[$caisse->id]) }} FCFA</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-caisses" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Caisse</label>
                    <select name="caisse_id" class="form-select form-select-sm select2-filtre-caisse">
                        <option value="">Toutes</option>
                        @foreach ($caisses as $caisse)
                            <option value="{{ $caisse->id }}">{{ $caisse->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Sens</label>
                    <select name="sens" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="entree">Entrée</option>
                        <option value="sortie">Sortie</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 al-filtres-actions justify-content-md-end">
                    <x-filtre-reset id="btn-reset-caisses" />
                    <x-export-dropdown id-suffix="caisses" />
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-caisses">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Caisse</th>
                        <th>Sens</th>
                        <th class="text-end">Montant</th>
                        <th>Mode de paiement</th>
                        <th>Motif</th>
                        <th>Enregistré par</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-caisse').select2({ width: '100%', selectionCssClass: 'select2-sm' });

            function filtresCaisses() {
                return {
                    date_debut: $('#filtres-caisses [name=date_debut]').val(),
                    date_fin: $('#filtres-caisses [name=date_fin]').val(),
                    caisse_id: $('#filtres-caisses [name=caisse_id]').val(),
                    sens: $('#filtres-caisses [name=sens]').val(),
                };
            }

            function actualiserBoutonResetCaisses() {
                const actif = Object.values(filtresCaisses()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-caisses').toggleClass('d-none', !actif);
            }

            $('#filtres-caisses').on('change input', actualiserBoutonResetCaisses);
            actualiserBoutonResetCaisses();

            const tableCaisses = $('#table-caisses').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.caisses.data') }}',
                    data: (d) => Object.assign(d, filtresCaisses()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'date_mouvement', name: 'date_mouvement' },
                    { data: 'caisse_libelle', name: 'caisse.libelle', orderable: false },
                    { data: 'sens_badge', name: 'sens', orderable: false },
                    { data: 'montant', name: 'montant', className: 'text-end' },
                    { data: 'mode_paiement_libelle', name: 'mode_paiement.libelle', orderable: false },
                    { data: 'motif', name: 'motif' },
                    { data: 'enregistre_par', name: 'user.name', orderable: false },
                ],
                order: [[0, 'desc']],
            });

            $('#filtres-caisses').on('change', () => tableCaisses.ajax.reload());

            $('#btn-reset-caisses').on('click', function () {
                $('#filtres-caisses')[0].reset();
                $('.select2-filtre-caisse').val('').trigger('change');
                tableCaisses.ajax.reload();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresCaisses());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-caisses').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('admin.caisses.export.excel') }}');
            });

            $('#btn-export-pdf-caisses').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('admin.caisses.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
