<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MatchingResource;
use App\Models\Matching;
use App\Models\Offre;
use App\Models\ProfilEtudiant;
use App\Services\MatchingService;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Liste des matchings pour l'étudiant connecté
     */
    public function index(Request $request)
    {
        $query = Matching::with(['offre', 'offre.recruteur', 'offre.offreCompetences.competence'])
                        ->where('etudiant_id', $request->user()->id);

        // Filtres
        if ($request->has('interesse')) {
            $query->whereNotNull('interesse');
            if ($request->interesse === 'true') {
                $query->where('interesse', true);
            } else {
                $query->where('interesse', false);
            }
        }

        if ($request->has('min_score')) {
            $query->where('score_global', '>=', $request->min_score);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'score_global');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $matchings = $query->paginate($request->get('per_page', 15));

        return MatchingResource::collection($matchings);
    }

    /**
     * Afficher un matching
     */
    public function show(Request $request, $id)
    {
        $matching = Matching::with(['offre', 'offre.recruteur', 'offre.offreCompetences.competence', 'etudiant'])
                           ->findOrFail($id);

        // Vérifier les permissions
        if ($matching->etudiant_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return new MatchingResource($matching);
    }

    /**
     * Marquer un matching comme vu
     */
    public function markAsViewed(Request $request, $id)
    {
        $matching = Matching::findOrFail($id);

        // Vérifier les permissions
        if ($matching->etudiant_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $matching->update([
            'vu_par_etudiant' => true,
            'date_vue_etudiant' => now(),
        ]);

        return response()->json([
            'message' => 'Matching marqué comme vu',
            'matching' => new MatchingResource($matching->fresh()),
        ]);
    }

    /**
     * Définir l'intérêt pour un matching
     */
    public function setInterest(Request $request, $id)
    {
        $matching = Matching::findOrFail($id);

        // Vérifier les permissions
        if ($matching->etudiant_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'interesse' => 'required|boolean',
        ]);

        $matching->update([
            'interesse' => $validated['interesse'],
        ]);

        return response()->json([
            'message' => $validated['interesse'] ? 'Intérêt marqué' : 'Intérêt retiré',
            'matching' => new MatchingResource($matching->fresh()),
        ]);
    }

    /**
     * Calculer les matchings pour toutes les offres (admin uniquement)
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'offre_id' => 'sometimes|exists:offres,id',
            'force' => 'sometimes|boolean',
        ]);

        $result = $this->matchingService->calculateMatchings(
            $validated['offre_id'] ?? null,
            $validated['force'] ?? false
        );

        return response()->json([
            'message' => 'Calcul des matchings terminé',
            'result' => $result,
        ]);
    }
}

