<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ProfileController extends Controller
{
    /**
     * Fiche du compte connecté : informations, activité récente et, pour
     * les administrateurs uniquement, formulaires de modification.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('roles');

        $activitesRecentes = Activity::where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->id)
            ->latest('id')
            ->limit(8)
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'peutModifier' => $user->can('profil.modifier'),
            'activitesRecentes' => $activitesRecentes,
            'nombreActivitesDuMois' => Activity::where('causer_type', $user->getMorphClass())
                ->where('causer_id', $user->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'nombreVehiculesAttribues' => $user->hasRole('gestionnaire') ? $user->vehiculesAttribues()->count() : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $modifications = $user->getDirty();
        $anciennes = array_intersect_key($user->getOriginal(), $modifications);

        $user->save();

        if ($modifications !== []) {
            activity()
                ->performedOn($user)
                ->event('updated')
                ->withProperties(['old' => $anciennes, 'attributes' => $modifications])
                ->log("Profil de « {$user->name} » mis à jour (depuis Mon profil).");
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill(['password' => Hash::make($request->validated('password'))])->save();

        activity()->performedOn($user)->event('updated')->log("Mot de passe de « {$user->name} » modifié (depuis Mon profil).");

        return Redirect::route('profile.edit')->with('status', 'password-updated');
    }
}
