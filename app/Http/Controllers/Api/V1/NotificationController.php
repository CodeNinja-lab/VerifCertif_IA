<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste des notifications
     */
    public function index(Request $request)
    {
        $query = Notification::where('destinataire_id', $request->user()->id);

        // Filtres
        if ($request->has('lue')) {
            $query->where('lue', $request->lue === 'true');
        }

        if ($request->has('archivee')) {
            $query->where('archivee', $request->archivee === 'true');
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('priorite')) {
            $query->where('priorite', $request->priorite);
        }

        $notifications = $query->orderBy('date_envoi', 'desc')
                              ->paginate($request->get('per_page', 20));

        return NotificationResource::collection($notifications);
    }

    /**
     * Notifications non lues
     */
    public function unread(Request $request)
    {
        $notifications = Notification::where('destinataire_id', $request->user()->id)
                                    ->where('lue', false)
                                    ->where('archivee', false)
                                    ->orderBy('date_envoi', 'desc')
                                    ->limit(50)
                                    ->get();

        return NotificationResource::collection($notifications);
    }

    /**
     * Afficher une notification
     */
    public function show(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier les permissions
        if ($notification->destinataire_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return new NotificationResource($notification);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier les permissions
        if ($notification->destinataire_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if (!$notification->lue) {
            $notification->update([
                'lue' => true,
                'date_lecture' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        $count = Notification::where('destinataire_id', $request->user()->id)
                            ->where('lue', false)
                            ->update([
                                'lue' => true,
                                'date_lecture' => now(),
                            ]);

        return response()->json([
            'message' => "$count notifications marquées comme lues",
            'count' => $count,
        ]);
    }

    /**
     * Archiver une notification
     */
    public function archive(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier les permissions
        if ($notification->destinataire_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $notification->update(['archivee' => true]);

        return response()->json([
            'message' => 'Notification archivée',
            'notification' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier les permissions
        if ($notification->destinataire_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification supprimée avec succès',
        ]);
    }
}

