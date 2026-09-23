<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Admin\CaissesExport;
use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class CaisseController extends Controller
{
    // Accès protégé par le middleware de route 'permission:caisse.voir' (routes/admin.php).
    public function index(): View
    {
        $caisses = Caisse::where('actif', true)->orderBy('id')->get();
        $soldes = $this->calculerSoldes($caisses);

        return view('admin.caisses.index', compact('caisses', 'soldes'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->filtrer(MouvementCaisse::query()->with(['caisse', 'modePaiement', 'user']), $request)
            ->select('mouvements_caisse.*');

        return DataTables::of($query)
            ->editColumn('date_mouvement', fn (MouvementCaisse $m) => $m->date_mouvement->format('d/m/Y H:i'))
            ->addColumn('caisse_libelle', fn (MouvementCaisse $m) => $m->caisse->libelle)
            ->addColumn('sens_badge', fn (MouvementCaisse $m) => $m->sens === 'entree'
                ? '<span class="badge bg-success">Entrée</span>'
                : '<span class="badge bg-danger">Sortie</span>')
            ->editColumn('montant', fn (MouvementCaisse $m) => Money::format($m->montant).' FCFA')
            ->addColumn('mode_paiement_libelle', fn (MouvementCaisse $m) => $m->modePaiement?->libelle ?? '—')
            ->addColumn('enregistre_par', fn (MouvementCaisse $m) => $m->user?->name ?? '—')
            ->rawColumns(['sens_badge'])
            ->make(true);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $mouvements = $this->filtrer(MouvementCaisse::query()->with(['caisse', 'modePaiement', 'user']), $request)
            ->orderByDesc('date_mouvement')
            ->get();

        return Excel::download(new CaissesExport($mouvements), 'caisses-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        $mouvements = $this->filtrer(MouvementCaisse::query()->with(['caisse', 'modePaiement', 'user']), $request)
            ->orderByDesc('date_mouvement')
            ->get();

        return Pdf::loadView('exports.pdf.caisses', ['mouvements' => $mouvements])
            ->setPaper('a4', 'landscape')
            ->download('caisses-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('caisse_id'), fn (Builder $q) => $q->where('caisse_id', $request->integer('caisse_id')))
            ->when($request->filled('sens'), fn (Builder $q) => $q->where('sens', $request->string('sens')))
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_mouvement', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_mouvement', '<=', $request->string('date_fin')));
    }

    /**
     * Solde courant de chaque caisse (entrées - sorties, tout historique
     * confondu), calculé en une seule requête groupée plutôt qu'une par caisse.
     *
     * @param  Collection<int, Caisse>  $caisses
     * @return array<int, float> solde indexé par caisse_id
     */
    private function calculerSoldes(Collection $caisses): array
    {
        $totaux = MouvementCaisse::query()
            ->selectRaw("caisse_id, SUM(CASE WHEN sens = 'entree' THEN montant ELSE -montant END) as solde")
            ->groupBy('caisse_id')
            ->pluck('solde', 'caisse_id');

        return $caisses->mapWithKeys(fn (Caisse $c) => [$c->id => (float) ($totaux[$c->id] ?? 0)])->all();
    }
}
