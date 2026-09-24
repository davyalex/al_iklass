<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Admin\AuditLogExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    // Accès protégé par le middleware de route 'permission:audit.voir' (routes/admin.php),
    // pas par une policy : Activity est un modèle du package, pas un modèle applicatif.
    public function index(): View
    {
        $utilisateurs = User::withTrashed()->orderBy('name')->get();
        $evenements = AuditService::EVENEMENTS;
        $elements = AuditService::modelesAudites();

        return view('admin.audit.index', compact('utilisateurs', 'evenements', 'elements'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->filtrer(Activity::query(), $request)->with('causer')->latest('id');

        return DataTables::of($query)
            ->editColumn('created_at', fn (Activity $a) => $a->created_at->format('d/m/Y H:i'))
            ->addColumn('causeur', fn (Activity $a) => $a->causer?->name ?? 'Système')
            ->addColumn('evenement', fn (Activity $a) => AuditService::EVENEMENTS[$a->event]
                ?? ['libelle' => 'Action', 'couleur' => 'light'])
            ->addColumn('element', fn (Activity $a) => $a->subject_type ? AuditService::libelleModele($a->subject_type) : '—')
            ->addColumn('changements', fn (Activity $a) => $this->changements($a))
            ->make(true);
    }

    /**
     * Compteurs affichés en tête du journal, calculés sur les mêmes filtres
     * que la liste.
     */
    public function kpis(Request $request): JsonResponse
    {
        $base = fn () => $this->filtrer(Activity::query(), $request);

        return response()->json([
            'total' => $base()->count(),
            'aujourdhui' => $base()->whereDate('created_at', today())->count(),
            'utilisateurs' => $base()->whereNotNull('causer_id')->distinct()->count('causer_id'),
        ]);
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
            ->when($request->filled('evenement'), fn (Builder $q) => $q->where('event', $request->string('evenement')))
            ->when($request->filled('element'), fn (Builder $q) => $q->where('subject_type', $request->string('element')));
    }

    /**
     * Détail lisible des valeurs enregistrées : avant → après pour une
     * modification, valeurs saisies pour une création/suppression.
     *
     * @return list<array{champ: string, avant: ?string, apres: ?string}>
     */
    private function changements(Activity $activite): array
    {
        $nouvelles = (array) $activite->properties->get('attributes', []);
        $anciennes = (array) $activite->properties->get('old', []);

        $lignes = [];
        foreach ($nouvelles as $champ => $valeur) {
            if ($champ === 'id') {
                continue;
            }

            $lignes[] = [
                'champ' => Str::of($champ)->replaceEnd('_id', '')->headline()->toString(),
                'avant' => array_key_exists($champ, $anciennes) ? $this->formaterValeur($anciennes[$champ]) : null,
                'apres' => $this->formaterValeur($valeur),
            ];
        }

        return $lignes;
    }

    private function formaterValeur(mixed $valeur): string
    {
        return match (true) {
            $valeur === null || $valeur === '' => '—',
            is_bool($valeur) => $valeur ? 'Oui' : 'Non',
            is_array($valeur) => json_encode($valeur, JSON_UNESCAPED_UNICODE),
            default => Str::limit((string) $valeur, 200),
        };
    }
}
