<?php

namespace App\Http\Controllers\Stock;

use App\Exports\Stock\InventairesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreInventaireRequest;
use App\Http\Requests\Stock\UpdateComptageInventaireLigneRequest;
use App\Models\CategorieArticle;
use App\Models\Inventaire;
use App\Models\InventaireLigne;
use App\Services\Stock\InventaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class InventaireController extends Controller
{
    public function __construct(private readonly InventaireService $inventaireService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Inventaire::class);

        $categories = CategorieArticle::where('actif', true)->orderBy('libelle')->get();

        return view('stock.inventaires.index', compact('categories'));
    }

    public function show(Inventaire $inventaire): JsonResponse
    {
        Gate::authorize('view', $inventaire);

        return response()->json($inventaire->load('lignes', 'categorie', 'valideParUtilisateur'));
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Inventaire::class);

        $query = $this->filtrer(Inventaire::query(), $request)->with('categorie')->withCount([
            'lignes',
            'lignes as lignes_comptees_count' => fn (Builder $q) => $q->whereNotNull('quantite_comptee'),
        ]);

        return DataTables::of($query)
            ->editColumn('date_inventaire', fn (Inventaire $i) => $i->date_inventaire->format('d/m/Y'))
            ->addColumn('categorie_libelle', fn (Inventaire $i) => $i->categorie?->libelle ?? 'Tous les articles')
            ->addColumn('avancement', fn (Inventaire $i) => $i->lignes_comptees_count.' / '.$i->lignes_count)
            ->addColumn('statut_badge', fn (Inventaire $i) => $i->statut === 'valide'
                ? '<span class="badge bg-success">Validé</span>'
                : '<span class="badge bg-secondary">Brouillon</span>')
            ->rawColumns(['statut_badge'])
            ->make(true);
    }

    public function store(StoreInventaireRequest $request): JsonResponse
    {
        $inventaire = $this->inventaireService->creer($request->validated() + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => "Inventaire {$inventaire->reference} créé.",
            'inventaire' => $inventaire,
        ], 201);
    }

    public function updateLigne(UpdateComptageInventaireLigneRequest $request, InventaireLigne $ligne): JsonResponse
    {
        Gate::authorize('update', $ligne->inventaire);

        try {
            $ligne = $this->inventaireService->enregistrerComptage(
                $ligne,
                $request->validated('quantite_comptee'),
                $request->validated('commentaire'),
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['ligne' => $ligne]);
    }

    public function valider(Request $request, Inventaire $inventaire): JsonResponse
    {
        Gate::authorize('update', $inventaire);

        try {
            $inventaire = $this->inventaireService->valider($inventaire, $request->user()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'message' => "Inventaire {$inventaire->reference} validé, le stock a été mis à jour.",
            'inventaire' => $inventaire,
        ]);
    }

    public function destroy(Inventaire $inventaire): JsonResponse
    {
        Gate::authorize('delete', $inventaire);

        try {
            $this->inventaireService->supprimer($inventaire);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['message' => "Inventaire {$inventaire->reference} supprimé."]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Inventaire::class);

        $inventaires = $this->filtrer(Inventaire::query(), $request)->with('lignes', 'categorie')->orderByDesc('date_inventaire')->get();

        return Excel::download(new InventairesExport($inventaires), 'inventaires-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Inventaire::class);

        $inventaires = $this->filtrer(Inventaire::query(), $request)->with('lignes', 'categorie')->orderByDesc('date_inventaire')->get();

        return Pdf::loadView('exports.pdf.inventaires', ['inventaires' => $inventaires])
            ->setPaper('a4', 'landscape')
            ->download('inventaires-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function filtrer(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_debut'), fn (Builder $q) => $q->whereDate('date_inventaire', '>=', $request->string('date_debut')))
            ->when($request->filled('date_fin'), fn (Builder $q) => $q->whereDate('date_inventaire', '<=', $request->string('date_fin')))
            ->when($request->filled('categorie_id'), fn (Builder $q) => $q->where('categorie_id', $request->integer('categorie_id')))
            ->when($request->filled('statut'), fn (Builder $q) => $q->where('statut', $request->string('statut')));
    }
}
