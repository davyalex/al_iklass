@if (empty($sauvegardes))
    <p class="small text-muted mb-0">Aucune sauvegarde pour le moment.</p>
@else
    <div class="list-group list-group-flush small">
        @foreach ($sauvegardes as $sauvegarde)
            <div class="list-group-item px-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-medium">{{ $sauvegarde['nom'] }}</div>
                    <div class="text-muted">
                        {{ $sauvegarde['date'] instanceof \Carbon\Carbon ? $sauvegarde['date']->format('d/m/Y H:i') : $sauvegarde['date'] }}
                        · {{ number_format($sauvegarde['taille'] / 1048576, 2) }} Mo
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('admin.parametres.sauvegardes.telecharger', $sauvegarde['nom']) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download"></i>
                    </a>
                    @hasrole('superadmin')
                        <button type="button" class="btn btn-sm btn-outline-danger btn-restaurer-sauvegarde" data-nom="{{ $sauvegarde['nom'] }}">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurer
                        </button>
                    @endhasrole
                </div>
            </div>
        @endforeach
    </div>
@endif
