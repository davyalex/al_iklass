@props(['idSuffix'])

<div class="dropdown">
    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-download me-1"></i>Exporter
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a href="#" id="btn-export-excel-{{ $idSuffix }}" class="dropdown-item">
                <i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel
            </a>
        </li>
        <li>
            <a href="#" id="btn-export-pdf-{{ $idSuffix }}" class="dropdown-item">
                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>PDF
            </a>
        </li>
    </ul>
</div>
