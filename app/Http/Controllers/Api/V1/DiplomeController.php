<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDiplomeRequest;
use App\Http\Requests\Api\V1\UpdateDiplomeRequest;
use App\Http\Resources\Api\V1\DiplomeResource;
use App\Models\Diplome;
use Illuminate\Http\Request;

class DiplomeController extends Controller
{
    /**
     * Liste des diplômes
     */
    public function index(Request $request)
    {
        $query = Diplome::with('competences');

        // Filtre par statut actif
        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'ordre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $diplomes = $query->paginate($request->get('per_page', 50));

        return DiplomeResource::collection($diplomes);
    }

    /**
     * Créer un diplôme
     */
    public function store(StoreDiplomeRequest $request)
    {
        try {
            $validated = $request->validated();
            $competences = $validated['competences'] ?? [];
            unset($validated['competences']);

            // S'assurer que les valeurs sont correctement formatées
            if (isset($validated['actif'])) {
                $validated['actif'] = (bool) $validated['actif'];
            }
            if (isset($validated['ordre'])) {
                $validated['ordre'] = (int) $validated['ordre'];
            }
            if (isset($validated['code']) && empty(trim($validated['code']))) {
                $validated['code'] = null;
            }

            $diplome = Diplome::create($validated);

            // Attacher les compétences avec ordre
            if (!empty($competences)) {
                $syncData = [];
                foreach ($competences as $index => $competenceId) {
                    $syncData[(int) $competenceId] = ['ordre' => (int) $index];
                }
                $diplome->competences()->sync($syncData);
            }

            return response()->json([
                'message' => 'Diplôme créé avec succès',
                'data' => new DiplomeResource($diplome->load('competences')),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du diplôme', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du diplôme',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Afficher un diplôme
     */
    public function show($id)
    {
        $diplome = Diplome::with('competences')->findOrFail($id);
        return new DiplomeResource($diplome);
    }

    /**
     * Mettre à jour un diplôme
     */
    public function update(UpdateDiplomeRequest $request, $id)
    {
        $diplome = Diplome::findOrFail($id);
        $validated = $request->validated();
        $competences = $validated['competences'] ?? null;
        unset($validated['competences']);

        $diplome->update($validated);

        // Mettre à jour les compétences si fournies
        if ($competences !== null) {
            $syncData = [];
            foreach ($competences as $index => $competenceId) {
                $syncData[$competenceId] = ['ordre' => $index];
            }
            $diplome->competences()->sync($syncData);
        }

        return response()->json([
            'message' => 'Diplôme mis à jour avec succès',
            'diplome' => new DiplomeResource($diplome->fresh()->load('competences')),
        ]);
    }

    /**
     * Supprimer un diplôme
     */
    public function destroy($id)
    {
        $diplome = Diplome::findOrFail($id);
        $diplome->delete();

        return response()->json([
            'message' => 'Diplôme supprimé avec succès',
        ]);
    }
}