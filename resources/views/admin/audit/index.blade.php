<x-app-layout>
    <x-slot name="header">Journal d'audit</x-slot>

    {{-- KPI --}}
    <div class="row g-2 g-sm-3 mb-3 row-cols-2 row-cols-sm-3">
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Actions aujourd'hui</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-audit-aujourdhui">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Sur la sélection</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-audit-total">—</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body">
                    <div class="small text-muted">Utilisateurs actifs</div>
                    <div class="h5 mb-0" style="color: var(--al-navy);" id="kpi-audit-utilisateurs">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card al-filtres mb-3">
        <div class="card-body">
            <form id="filtres-audit" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Utilisateur</label>
                    <select name="causer_id" class="form-select form-select-sm select2-filtre-utilisateur">
                        <option value="">Tous</option>
                        @foreach ($utilisateurs as $utilisateur)
                            <option value="{{ $utilisateur->id }}">{{ $utilisateur->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Type d'action</label>
                    <select name="evenement" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($evenements as $code => $evenement)
                            <option value="{{ $code }}">{{ $evenement['libelle'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Élément</label>
                    <select name="element" class="form-select form-select-sm select2-filtre-element">
                        <option value="">Tous</option>
                        @foreach ($elements as $classe => $libelle)
                            <option value="{{ $classe }}">{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 al-filtres-actions justify-content-md-end">
                    <x-filtre-reset id="btn-reset-audit" />
                    <x-export-dropdown id-suffix="audit" />
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-light border small d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle text-primary"></i>
        <span>Chaque création, modification, suppression et connexion est enregistrée. Le journal est vidé automatiquement le 1<sup>er</sup> de chaque mois (les 30 derniers jours sont conservés).</span>
    </div>

    <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100" id="table-audit">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Type</th>
                        <th>Élément</th>
                        <th>Action</th>
                        <th class="text-end">Détail</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Détail d'une entrée : valeurs avant / après --}}
    <div class="modal fade" id="modal-detail-audit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détail de l'action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="fw-semibold" id="detail-audit-description"></div>
                        <div class="small text-muted" id="detail-audit-meta"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Champ</th>
                                    <th id="detail-audit-col-avant">Avant</th>
                                    <th id="detail-audit-col-apres">Après</th>
                                </tr>
                            </thead>
                            <tbody id="detail-audit-lignes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2-filtre-utilisateur').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });
            $('.select2-filtre-element').select2({ width: '100%', placeholder: 'Tous', selectionCssClass: 'select2-sm' });

            const echapper = (texte) => $('<div>').text(texte ?? '').html();

            function filtresAudit() {
                return {
                    date_debut: $('#filtres-audit [name=date_debut]').val(),
                    date_fin: $('#filtres-audit [name=date_fin]').val(),
                    causer_id: $('#filtres-audit [name=causer_id]').val(),
                    evenement: $('#filtres-audit [name=evenement]').val(),
                    element: $('#filtres-audit [name=element]').val(),
                };
            }

            function actualiserBoutonResetAudit() {
                const actif = Object.values(filtresAudit()).some((v) => v !== undefined && v !== null && v !== '');
                $('#btn-reset-audit').toggleClass('d-none', !actif);
            }

            function chargerKpisAudit() {
                $.get('{{ route('admin.audit.kpis') }}', filtresAudit(), function (kpis) {
                    $('#kpi-audit-aujourdhui').text(kpis.aujourdhui);
                    $('#kpi-audit-total').text(kpis.total);
                    $('#kpi-audit-utilisateurs').text(kpis.utilisateurs);
                });
            }

            actualiserBoutonResetAudit();
            chargerKpisAudit();

            const tableAudit = $('#table-audit').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.audit.data') }}',
                    data: (d) => Object.assign(d, filtresAudit()),
                },
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fr-FR.json' },
                columns: [
                    { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
                    { data: 'causeur', name: 'causer.name', orderable: false },
                    {
                        data: 'evenement', name: 'event', orderable: false,
                        render: (e) => `<span class="badge text-bg-${e.couleur}">${echapper(e.libelle)}</span>`,
                    },
                    { data: 'element', name: 'subject_type', orderable: false, className: 'd-none d-md-table-cell' },
                    { data: 'description', name: 'description' },
                    {
                        data: 'changements', orderable: false, searchable: false, className: 'text-end',
                        render: (lignes) => lignes.length
                            ? '<button type="button" class="btn btn-sm btn-outline-secondary btn-detail-audit" title="Voir le détail"><i class="bi bi-eye"></i></button>'
                            : '',
                    },
                ],
                order: [[0, 'desc']],
            });

            $('#table-audit').on('click', '.btn-detail-audit', function () {
                const ligne = tableAudit.row($(this).closest('tr')).data();
                const estModification = ligne.changements.some((c) => c.avant !== null);

                $('#detail-audit-description').text(ligne.description);
                $('#detail-audit-meta').text(`${ligne.created_at} · ${ligne.causeur} · ${ligne.evenement.libelle}`);
                $('#detail-audit-col-avant').toggleClass('d-none', !estModification);
                $('#detail-audit-col-apres').text(estModification ? 'Après' : 'Valeur');

                $('#detail-audit-lignes').html(ligne.changements.map((c) => `
                    <tr>
                        <td class="text-muted small">${echapper(c.champ)}</td>
                        ${estModification ? `<td class="text-danger text-decoration-line-through small">${echapper(c.avant)}</td>` : ''}
                        <td class="fw-semibold small">${echapper(c.apres)}</td>
                    </tr>`).join(''));

                bootstrap.Modal.getOrCreateInstance('#modal-detail-audit').show();
            });

            $('#filtres-audit').on('change', function () {
                actualiserBoutonResetAudit();
                tableAudit.ajax.reload();
                chargerKpisAudit();
            });

            $('#btn-reset-audit').on('click', function () {
                $('#filtres-audit')[0].reset();
                $('.select2-filtre-utilisateur, .select2-filtre-element').val('').trigger('change.select2');
                actualiserBoutonResetAudit();
                tableAudit.ajax.reload();
                chargerKpisAudit();
            });

            function urlAvecFiltres(base) {
                const params = new URLSearchParams(filtresAudit());
                return base + '?' + params.toString();
            }

            $('#btn-export-excel-audit').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('admin.audit.export.excel') }}');
            });

            $('#btn-export-pdf-audit').on('click', function (e) {
                e.preventDefault();
                window.location = urlAvecFiltres('{{ route('admin.audit.export.pdf') }}');
            });
        });
        </script>
    @endpush
</x-app-layout>
