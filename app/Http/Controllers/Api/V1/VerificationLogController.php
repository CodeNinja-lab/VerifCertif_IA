<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VerificationLogResource;
use App\Models\VerificationLog;
use Illuminate\Http\Request;

class VerificationLogController extends Controller
{
    /**
     * Liste des logs de vérification
     */
    public function index(Request $request)
    {
        $query = VerificationLog::with(['document', 'verificateur']);

        // Filtres
        if ($request->has('document_id')) {
            $query->where('document_id', $request->document_id);
        }

        if ($request->has('resultat')) {
            $query->where('resultat', $request->resultat);
        }

        if ($request->has('verificateur_type')) {
            $query->where('verificateur_type', $request->verificateur_type);
        }

        if ($request->has('date_debut')) {
            $query->where('date_verification', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_verification', '<=', $request->date_fin);
        }

        $logs = $query->orderBy('date_verification', 'desc')
                     ->paginate($request->get('per_page', 50));

        return VerificationLogResource::collection($logs);
    }

    /**
     * Afficher un log de vérification
     */
    public function show($id)
    {
        $log = VerificationLog::with(['document', 'verificateur'])->findOrFail($id);
        return new VerificationLogResource($log);
    }
}

