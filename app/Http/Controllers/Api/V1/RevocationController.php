<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRevocationRequest;
use App\Http\Resources\Api\V1\RevocationResource;
use App\Models\Revocation;
use Illuminate\Http\Request;

class RevocationController extends Controller
{
    /**
     * Liste des révocations
     */
    public function index(Request $request)
    {
        $query = Revocation::with(['document', 'administration', 'operateur']);

        // Filtres
        if ($request->has('document_id')) {
            $query->where('document_id', $request->document_id);
        }

        if ($request->has('administration_id')) {
            $query->where('administration_id', $request->administration_id);
        }

        if ($request->has('motif_categorie')) {
            $query->where('motif_categorie', $request->motif_categorie);
        }

        $revocations = $query->orderBy('date_revocation', 'desc')
                            ->paginate($request->get('per_page', 15));

        return RevocationResource::collection($revocations);
    }

    /**
     * Afficher une révocation
     */
    public function show($id)
    {
        $revocation = Revocation::with(['document', 'administration', 'operateur'])->findOrFail($id);
        return new RevocationResource($revocation);
    }

    /**
     * Créer une révocation
     */
    public function store(StoreRevocationRequest $request)
    {
        $validated = $request->validated();

        $revocation = Revocation::create([
            'document_id' => $validated['document_id'],
            'administration_id' => $validated['administration_id'],
            'operateur_id' => $request->user()->id,
            'motif_categorie' => $validated['motif_categorie'],
            'motif_detail' => $validated['motif_detail'],
            'document_justificatif_url' => $validated['document_justificatif_url'] ?? null,
            'irreversible' => $validated['irreversible'] ?? true,
        ]);

        // Mettre à jour le statut du document
        $revocation->document->update(['statut' => 'REVOQUE']);

        return response()->json([
            'message' => 'Révocation créée avec succès',
            'revocation' => new RevocationResource($revocation->load(['document', 'administration', 'operateur'])),
        ], 201);
    }
}

