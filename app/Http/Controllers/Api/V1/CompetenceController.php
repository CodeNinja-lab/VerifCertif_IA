<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCompetenceRequest;
use App\Http\Requests\Api\V1\UpdateCompetenceRequest;
use App\Http\Resources\Api\V1\CompetenceResource;
use App\Models\Competence;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CompetenceController extends Controller
{
    /**
     * Liste des compétences
     */
    public function index(Request $request)
    {
        $query = Competence::query();

        // Filtres
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('nom_normalise', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'popularite');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $competences = $query->paginate($request->get('per_page', 50));

        return CompetenceResource::collection($competences);
    }

    /**
     * Afficher une compétence
     */
    public function show($id)
    {
        $competence = Competence::findOrFail($id);
        return new CompetenceResource($competence);
    }

    /**
     * Rechercher des compétences
     */
    public function search(Request $request, $query)
    {
        $competences = Competence::where('nom', 'like', "%{$query}%")
                                 ->orWhere('nom_normalise', 'like', "%{$query}%")
                                 ->orWhereJsonContains('synonymes', $query)
                                 ->orderBy('popularite', 'desc')
                                 ->limit(20)
                                 ->get();

        return CompetenceResource::collection($competences);
    }

    /**
     * Créer une compétence
     */
    public function store(StoreCompetenceRequest $request)
    {
        $validated = $request->validated();

        $competence = Competence::create([
            'nom' => $validated['nom'],
            'nom_normalise' => strtolower($validated['nom_normalise'] ?? $validated['nom']),
            'categorie' => $validated['categorie'],
            'description' => $validated['description'] ?? null,
            'referentiel_externe_id' => $validated['referentiel_externe_id'] ?? null,
            'synonymes' => $validated['synonymes'] ?? null,
            'popularite' => 0,
            'date_creation' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Compétence créée avec succès',
            'competence' => new CompetenceResource($competence),
        ], 201);
    }

    /**
     * Mettre à jour une compétence
     */
    public function update(UpdateCompetenceRequest $request, $id)
    {
        $competence = Competence::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['nom_normalise'])) {
            $validated['nom_normalise'] = strtolower($validated['nom_normalise']);
        }

        $competence->update($validated);

        return response()->json([
            'message' => 'Compétence mise à jour avec succès',
            'competence' => new CompetenceResource($competence->fresh()),
        ]);
    }

    /**
     * Supprimer une compétence
     */
    public function destroy($id)
    {
        $competence = Competence::findOrFail($id);
        $competence->delete();

        return response()->json([
            'message' => 'Compétence supprimée avec succès',
        ]);
    }
}

