<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when(request('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', request('role'))))
            ->when(request('statut') === 'actif', fn ($q) => $q->where('is_active', true))
            ->when(request('statut') === 'inactif', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json($user->load('roles'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $resultat = $this->userService->creer($request->validated());

        return response()->json([
            'message' => "Utilisateur « {$resultat['user']->name} » créé.",
            'user' => $resultat['user'],
            'password' => $resultat['password'],
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $user = $this->userService->mettreAJour($user, $request->validated());

        return response()->json([
            'message' => "Utilisateur « {$user->name} » mis à jour.",
            'user' => $user,
        ]);
    }

    public function resetPassword(User $user): JsonResponse
    {
        Gate::authorize('resetPassword', $user);

        $resultat = $this->userService->reinitialiserMotDePasse($user);

        return response()->json([
            'message' => "Mot de passe de « {$user->name} » réinitialisé.",
            'password' => $resultat['password'],
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        Gate::authorize('toggleActive', $user);

        $this->userService->activer($user);

        return response()->json(['message' => "Compte de « {$user->name} » activé."]);
    }

    public function deactivate(User $user): JsonResponse
    {
        Gate::authorize('toggleActive', $user);

        $this->userService->desactiver($user);

        return response()->json(['message' => "Compte de « {$user->name} » désactivé."]);
    }
}
