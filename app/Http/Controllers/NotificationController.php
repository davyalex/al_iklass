<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $utilisateur = $request->user();

        return response()->json([
            'non_lues' => $utilisateur->unreadNotifications()->count(),
            'notifications' => $utilisateur->notifications()->latest()->limit(15)->get()->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'vehicule_id' => $n->data['vehicule_id'] ?? null,
                'statut_alerte' => $n->data['statut_alerte'] ?? null,
                'lue' => $n->read_at !== null,
                'date' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function marquerLue(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['non_lues' => $request->user()->unreadNotifications()->count()]);
    }

    public function marquerToutLu(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['non_lues' => 0]);
    }
}
