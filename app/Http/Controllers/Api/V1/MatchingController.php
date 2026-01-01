<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MatchingResource;
use App\Models\Matching;
use App\Models\Offre;
use App\Models\ProfilEtudiant;
use App\Services\MatchingService;
use App\Services\EmbeddingService;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    protected $matchingService;
    protected $embeddingService;

    public function __construct(MatchingService $matchingService, EmbeddingService $embeddingService)
    {
        $this->matchingService = $matchingService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Liste des offres compatibles pour l'étudiant connecté (basé sur IA/embeddings)
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->get('limit', 20);
        $minScore = $request->get('min_score', 0);

        // Obtenir les recommandations IA
        $recommendations = $this->embeddingService->getCompatibleJobOffers($userId, $limit);

        // Filtrer par score minimum si demandé
        if ($minScore > 0) {
            $recommendations = array_filter($recommendations, function ($rec) use ($minScore) {
                return $rec['similarity_score'] >= $minScore;
            });
        }

        // Enrichir avec les données complètes des offres
        $offreIds = array_column($recommendations, 'offre_id');
        $offres = Offre::with(['recruteur', 'offreCompetences.competence'])
            ->whereIn('id', $offreIds)
            ->where('statut', 'PUBLIEE')
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($recommendations as $rec) {
            $offre = $offres->get($rec['offre_id']);
            if ($offre) {
                $results[] = [
                    'offre' => $offre,
                    'ai_score' => $rec['similarity_score'],
                    'title' => $rec['title'],
                ];
            }
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'algorithm' => 'AI Embeddings (Cosine Similarity)',
                'model' => 'all-MiniLM-L6-v2',
            ],
        ]);
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

