<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    // Accès protégé par le middleware de route 'permission:audit.view' (routes/admin.php),
    // pas par une policy : Activity est un modèle du package, pas un modèle applicatif.
    public function index(): View
    {
        return view('admin.audit.index');
    }

    public function data(): JsonResponse
    {
        $query = Activity::query()->with('causer')->latest('id');

        return DataTables::of($query)
            ->editColumn('created_at', fn (Activity $a) => $a->created_at->format('d/m/Y H:i'))
            ->addColumn('causeur', fn (Activity $a) => $a->causer?->name ?? 'Système')
            ->addColumn('description', fn (Activity $a) => $a->description)
            ->make(true);
    }
}
