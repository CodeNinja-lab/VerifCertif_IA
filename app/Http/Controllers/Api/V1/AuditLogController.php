<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Liste des logs d'audit
     */
    public function index(Request $request)
    {
        $query = AuditLog::with(['utilisateur']);

        // Filtres
        if ($request->has('utilisateur_id')) {
            $query->where('utilisateur_id', $request->utilisateur_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('objet_type')) {
            $query->where('objet_type', $request->objet_type);
        }

        if ($request->has('date_debut')) {
            $query->where('date_action', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_action', '<=', $request->date_fin);
        }

        $logs = $query->orderBy('date_action', 'desc')
                     ->paginate($request->get('per_page', 50));

        return AuditLogResource::collection($logs);
    }

    /**
     * Afficher un log d'audit
     */
    public function show($id)
    {
        $log = AuditLog::with(['utilisateur'])->findOrFail($id);
        return new AuditLogResource($log);
    }
}

