<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CandidatureResource;
use App\Models\Candidature;
use App\Models\Offre;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CandidatureController extends Controller
{
    /**
     * Liste des candidatures de l'étudiant connecté
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Candidature::with('offre')
            ->forEtudiant($user->id)
            ->orderBy('created_at', 'desc');
        
        // Filtrage par statut si fourni
        if ($request->has('statut') && $request->statut) {
            $query->byStatut($request->statut);
        }
        
        $candidatures = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => CandidatureResource::collection($candidatures),
        ]);
    }

    /**
     * Créer une nouvelle candidature
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
            'lettre_motivation' => 'nullable|string|max:5000',
            'cv_url' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        
        // Vérifier si l'étudiant n'a pas déjà postulé à cette offre
        $existingCandidature = Candidature::where('etudiant_id', $user->id)
            ->where('offre_id', $request->offre_id)
            ->first();
            
        if ($existingCandidature) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà postulé à cette offre.',
            ], 422);
        }
        
        // Vérifier que l'offre est encore active
        $offre = Offre::find($request->offre_id);
        if (!$offre || strtolower($offre->statut) !== 'publiee') {
            return response()->json([
                'success' => false,
                'message' => 'Cette offre n\'est plus disponible.',
            ], 422);
        }
        
        $candidature = Candidature::create([
            'etudiant_id' => $user->id,
            'offre_id' => $request->offre_id,
            'lettre_motivation' => $request->lettre_motivation,
            'cv_url' => $request->cv_url,
            'statut' => 'envoyee',
            'date_candidature' => now(),
        ]);
        
        // Incrémenter le compteur de candidatures sur l'offre
        $offre->increment('nombre_candidatures');
        
        $candidature->load('offre');
        
        return response()->json([
            'success' => true,
            'message' => 'Candidature envoyée avec succès.',
            'data' => new CandidatureResource($candidature),
        ], 201);
    }

    /**
     * Voir une candidature spécifique
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        
        $candidature = Candidature::with('offre')
            ->where('id', $id)
            ->where('etudiant_id', $user->id)
            ->first();
            
        if (!$candidature) {
            return response()->json([
                'success' => false,
                'message' => 'Candidature non trouvée.',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => new CandidatureResource($candidature),
        ]);
    }

    /**
     * Annuler/retirer une candidature (par l'étudiant)
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        
        $candidature = Candidature::where('id', $id)
            ->where('etudiant_id', $user->id)
            ->first();
            
        if (!$candidature) {
            return response()->json([
                'success' => false,
                'message' => 'Candidature non trouvée.',
            ], 404);
        }
        
        // On ne peut pas annuler une candidature déjà acceptée ou refusée
        if (in_array($candidature->statut, ['acceptee', 'refusee'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler cette candidature.',
            ], 422);
        }
        
        // Décrémenter le compteur de candidatures
        $offre = Offre::find($candidature->offre_id);
        if ($offre && $offre->nombre_candidatures > 0) {
            $offre->decrement('nombre_candidatures');
        }
        
        $candidature->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Candidature retirée avec succès.',
        ]);
    }

    /**
     * Mettre à jour le statut d'une candidature (par le recruteur)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'statut' => 'required|in:vue,en_cours,entretien,acceptee,refusee',
            'date_entretien' => 'nullable|date',
            'feedback' => 'nullable|string|max:2000',
            'notes_recruteur' => 'nullable|string|max:2000',
        ]);

        $candidature = Candidature::with('offre', 'etudiant')->find($id);
        
        if (!$candidature) {
            return response()->json([
                'success' => false,
                'message' => 'Candidature non trouvée.',
            ], 404);
        }
        
        // Vérifier que l'utilisateur est le recruteur de l'offre
        $user = Auth::user();
        if ($candidature->offre->recruteur_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette candidature.',
            ], 403);
        }
        
        $oldStatut = $candidature->statut;
        
        $candidature->update([
            'statut' => $request->statut,
            'date_entretien' => $request->date_entretien,
            'feedback' => $request->feedback,
            'notes_recruteur' => $request->notes_recruteur,
        ]);
        
        // Envoyer une notification à l'étudiant si le statut change
        if ($oldStatut !== $request->statut) {
            $notificationService = new NotificationService();
            
            // Si c'est la première consultation (vue), notifier la consultation
            if ($request->statut === 'vue' && $oldStatut === 'envoyee') {
                $notificationService->candidatureConsultee(
                    $candidature->etudiant_id,
                    $user->prenom . ' ' . $user->nom,
                    $candidature->offre->titre,
                    $candidature->id
                );
            } else {
                // Pour les autres changements de statut
                $notificationService->candidatureStatutChange(
                    $candidature->etudiant_id,
                    $request->statut,
                    $candidature->offre->titre,
                    $candidature->id
                );
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Statut de la candidature mis à jour.',
            'data' => new CandidatureResource($candidature),
        ]);
    }

    /**
     * Liste des candidatures pour une offre (pour le recruteur)
     */
    public function forOffre(int $offreId): JsonResponse
    {
        $user = Auth::user();
        
        $offre = Offre::find($offreId);
        
        if (!$offre) {
            return response()->json([
                'success' => false,
                'message' => 'Offre non trouvée.',
            ], 404);
        }
        
        // Vérifier que l'utilisateur est le recruteur de l'offre
        if ($offre->recruteur_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir ces candidatures.',
            ], 403);
        }
        
        $candidatures = Candidature::with('etudiant', 'etudiant.profilEtudiant')
            ->forOffre($offreId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => CandidatureResource::collection($candidatures),
        ]);
    }
}
