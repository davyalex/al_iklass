<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Admin\AuditLogExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    // Accès protégé par le middleware de route 'permission:audit.view' (routes/admin.php),
    // pas par une policy : Activity est un modèle du package, pas un modèle applicatif.
    public function index(): View
    {
        $utilisateurs = User::orderBy('name')->get();

        return view('admin.audit.index', compact('utilisateurs'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->filtrer(Activity::query(), $request)->with('causer')->latest('id');

        return DataTables::of($query)
            ->editColumn('created_at', fn (Activity $a) => $a->created_at->format('d/m/Y H:i'))
            ->addColumn('causeur', fn (Activity $a) => $a->causer?->name ?? 'Système')
            ->addColumn('description', fn (Activity $a) => $a->description)
            ->make(true);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $activites = $this->filtrer(Activity::query(), $request)->with('causer')->latest('id')->get();

        return Excel::download(new AuditLogExport($activites), 'journal-audit-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        $activites = $this->filtrer(Activity::query(), $request)->with('causer')->latest('id')->get();

        return Pdf::loadView('exports.pdf.audit', ['activites' => $activites])
            ->setPaper('a4', 'landscape')
            ->download('journal-audit-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_fin')))
            ->when($request->filled('causer_id'), fn (Builder $q) => $q->where('causer_id', $request->integer('causer_id')))
            ->when($request->filled('recherche'), fn (Builder $q) => $q->where('description', 'like', '%'.$request->string('recherche').'%'));
    }
}
