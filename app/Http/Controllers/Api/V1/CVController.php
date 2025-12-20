<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExperienceResource;
use App\Http\Resources\Api\V1\FormationResource;
use App\Http\Resources\Api\V1\CertificationResource;
use App\Models\Experience;
use App\Models\Formation;
use App\Models\Certification;
use Illuminate\Http\Request;

class CVController extends Controller
{
    /**
     * Obtenir toutes les données du CV de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $experiences = Experience::where('utilisateur_id', $userId)
            ->orderBy('poste_actuel', 'desc')
            ->orderBy('date_debut', 'desc')
            ->get();

        $formations = Formation::where('utilisateur_id', $userId)
            ->orderBy('en_cours', 'desc')
            ->orderBy('date_debut', 'desc')
            ->get();

        $certifications = Certification::where('utilisateur_id', $userId)
            ->orderBy('date_obtention', 'desc')
            ->get();

        return response()->json([
            'experiences' => ExperienceResource::collection($experiences),
            'formations' => FormationResource::collection($formations),
            'certifications' => CertificationResource::collection($certifications),
        ]);
    }

    // ===================== EXPERIENCES =====================

    /**
     * Liste des expériences
     */
    public function experienceIndex(Request $request)
    {
        $experiences = Experience::where('utilisateur_id', $request->user()->id)
            ->orderBy('poste_actuel', 'desc')
            ->orderBy('date_debut', 'desc')
            ->get();

        return ExperienceResource::collection($experiences);
    }

    /**
     * Créer une expérience
     */
    public function experienceStore(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'entreprise' => 'required|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'poste_actuel' => 'boolean',
            'description' => 'nullable|string',
            'realisations' => 'nullable|array',
            'realisations.*' => 'string',
        ]);

        $experience = Experience::create([
            'utilisateur_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Expérience ajoutée avec succès',
            'experience' => new ExperienceResource($experience),
        ], 201);
    }

    /**
     * Afficher une expérience
     */
    public function experienceShow(Request $request, $id)
    {
        $experience = Experience::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        return new ExperienceResource($experience);
    }

    /**
     * Mettre à jour une expérience
     */
    public function experienceUpdate(Request $request, $id)
    {
        $experience = Experience::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'entreprise' => 'sometimes|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'poste_actuel' => 'boolean',
            'description' => 'nullable|string',
            'realisations' => 'nullable|array',
            'realisations.*' => 'string',
        ]);

        $experience->update($validated);

        return response()->json([
            'message' => 'Expérience mise à jour avec succès',
            'experience' => new ExperienceResource($experience->fresh()),
        ]);
    }

    /**
     * Supprimer une expérience
     */
    public function experienceDestroy(Request $request, $id)
    {
        $experience = Experience::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        $experience->delete();

        return response()->json([
            'message' => 'Expérience supprimée avec succès',
        ]);
    }

    // ===================== FORMATIONS =====================

    /**
     * Liste des formations
     */
    public function formationIndex(Request $request)
    {
        $formations = Formation::where('utilisateur_id', $request->user()->id)
            ->orderBy('en_cours', 'desc')
            ->orderBy('date_debut', 'desc')
            ->get();

        return FormationResource::collection($formations);
    }

    /**
     * Créer une formation
     */
    public function formationStore(Request $request)
    {
        $validated = $request->validate([
            'diplome' => 'required|string|max:255',
            'etablissement' => 'required|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'en_cours' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $formation = Formation::create([
            'utilisateur_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Formation ajoutée avec succès',
            'formation' => new FormationResource($formation),
        ], 201);
    }

    /**
     * Afficher une formation
     */
    public function formationShow(Request $request, $id)
    {
        $formation = Formation::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        return new FormationResource($formation);
    }

    /**
     * Mettre à jour une formation
     */
    public function formationUpdate(Request $request, $id)
    {
        $formation = Formation::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'diplome' => 'sometimes|string|max:255',
            'etablissement' => 'sometimes|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'en_cours' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $formation->update($validated);

        return response()->json([
            'message' => 'Formation mise à jour avec succès',
            'formation' => new FormationResource($formation->fresh()),
        ]);
    }

    /**
     * Supprimer une formation
     */
    public function formationDestroy(Request $request, $id)
    {
        $formation = Formation::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        $formation->delete();

        return response()->json([
            'message' => 'Formation supprimée avec succès',
        ]);
    }

    // ===================== CERTIFICATIONS =====================

    /**
     * Liste des certifications
     */
    public function certificationIndex(Request $request)
    {
        $certifications = Certification::where('utilisateur_id', $request->user()->id)
            ->orderBy('date_obtention', 'desc')
            ->get();

        return CertificationResource::collection($certifications);
    }

    /**
     * Créer une certification
     */
    public function certificationStore(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'organisme' => 'required|string|max:255',
            'date_obtention' => 'required|date',
            'date_expiration' => 'nullable|date|after:date_obtention',
            'identifiant' => 'nullable|string|max:255',
            'url_verification' => 'nullable|url|max:500',
        ]);

        $certification = Certification::create([
            'utilisateur_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Certification ajoutée avec succès',
            'certification' => new CertificationResource($certification),
        ], 201);
    }

    /**
     * Afficher une certification
     */
    public function certificationShow(Request $request, $id)
    {
        $certification = Certification::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        return new CertificationResource($certification);
    }

    /**
     * Mettre à jour une certification
     */
    public function certificationUpdate(Request $request, $id)
    {
        $certification = Certification::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'organisme' => 'sometimes|string|max:255',
            'date_obtention' => 'sometimes|date',
            'date_expiration' => 'nullable|date|after:date_obtention',
            'identifiant' => 'nullable|string|max:255',
            'url_verification' => 'nullable|url|max:500',
        ]);

        $certification->update($validated);

        return response()->json([
            'message' => 'Certification mise à jour avec succès',
            'certification' => new CertificationResource($certification->fresh()),
        ]);
    }

    /**
     * Supprimer une certification
     */
    public function certificationDestroy(Request $request, $id)
    {
        $certification = Certification::where('utilisateur_id', $request->user()->id)
            ->findOrFail($id);

        $certification->delete();

        return response()->json([
            'message' => 'Certification supprimée avec succès',
        ]);
    }
}
