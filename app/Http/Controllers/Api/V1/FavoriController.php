<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OffreResource;
use App\Models\Favori;
use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FavoriController extends Controller
{
    /**
     * Liste des favoris de l'utilisateur connecté
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $favoris = Favori::with('offre')
            ->forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Retourner les offres avec un flag isFavorite
        $offres = $favoris->map(function ($favori) {
            $offre = $favori->offre;
            if ($offre) {
                $offreData = (new OffreResource($offre))->toArray(request());
                $offreData['is_favorite'] = true;
                $offreData['favorited_at'] = $favori->created_at->toIso8601String();
                return $offreData;
            }
            return null;
        })->filter();
        
        return response()->json([
            'success' => true,
            'data' => $offres->values(),
        ]);
    }

    /**
     * Ajouter une offre aux favoris
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
        ]);

        $user = Auth::user();
        
        // Vérifier si l'offre n'est pas déjà en favori
        $existing = Favori::where('user_id', $user->id)
            ->where('offre_id', $request->offre_id)
            ->first();
            
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Cette offre est déjà dans vos favoris.',
            ], 422);
        }
        
        $favori = Favori::create([
            'user_id' => $user->id,
            'offre_id' => $request->offre_id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Offre ajoutée aux favoris.',
            'data' => [
                'id' => $favori->id,
                'offre_id' => $favori->offre_id,
            ],
        ], 201);
    }

    /**
     * Retirer une offre des favoris
     */
    public function destroy(int $offreId): JsonResponse
    {
        $user = Auth::user();
        
        $favori = Favori::where('user_id', $user->id)
            ->where('offre_id', $offreId)
            ->first();
            
        if (!$favori) {
            return response()->json([
                'success' => false,
                'message' => 'Cette offre n\'est pas dans vos favoris.',
            ], 404);
        }
        
        $favori->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Offre retirée des favoris.',
        ]);
    }

    /**
     * Toggle favori (ajouter si pas présent, retirer sinon)
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
        ]);

        $user = Auth::user();
        
        $existing = Favori::where('user_id', $user->id)
            ->where('offre_id', $request->offre_id)
            ->first();
            
        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'message' => 'Offre retirée des favoris.',
                'is_favorite' => false,
            ]);
        }
        
        $favori = Favori::create([
            'user_id' => $user->id,
            'offre_id' => $request->offre_id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Offre ajoutée aux favoris.',
            'is_favorite' => true,
        ], 201);
    }

    /**
     * Vérifier si une offre est en favori
     */
    public function check(int $offreId): JsonResponse
    {
        $user = Auth::user();
        
        $isFavorite = Favori::where('user_id', $user->id)
            ->where('offre_id', $offreId)
            ->exists();
        
        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
        ]);
    }
}
