<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProfilEtudiantRequest;
use App\Http\Requests\Api\V1\UpdateProfilEtudiantRequest;
use App\Http\Requests\Api\V1\AddCompetenceRequest;
use App\Http\Requests\Api\V1\UpdateCompetenceRequest;
use App\Http\Resources\Api\V1\ProfilEtudiantResource;
use App\Http\Resources\Api\V1\ProfilCompetenceResource;
use App\Models\ProfilEtudiant;
use App\Models\ProfilCompetence;
use App\Models\Competence;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProfilEtudiantController extends Controller
{
    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function show(Request $request)
    {
        $profil = ProfilEtudiant::with(['utilisateur', 'profilCompetences.competence'])
                               ->where('utilisateur_id', $request->user()->id)
                               ->first();

        if (!$profil) {
            return response()->json(['message' => 'Profil non trouvé'], 404);
        }

        return new ProfilEtudiantResource($profil);
    }

    /**
     * Créer un profil étudiant
     */
    public function store(StoreProfilEtudiantRequest $request)
    {
        $validated = $request->validated();

        // Vérifier si le profil existe déjà
        $existingProfil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->first();
        if ($existingProfil) {
            return response()->json(['message' => 'Le profil existe déjà'], 422);
        }

        $profil = ProfilEtudiant::create([
            'utilisateur_id' => $request->user()->id,
            'bio' => $validated['bio'] ?? null,
            'cv_url' => $validated['cv_url'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'github_url' => $validated['github_url'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'disponibilite' => $validated['disponibilite'] ?? null,
            'localisation_actuelle' => $validated['localisation_actuelle'] ?? null,
            'localisation_souhaitee' => $validated['localisation_souhaitee'] ?? null,
            'mobilite' => $validated['mobilite'] ?? null,
            'salaire_minimum_souhaite' => $validated['salaire_minimum_souhaite'] ?? null,
            'types_contrat_souhaites' => $validated['types_contrat_souhaites'] ?? null,
            'profil_public' => $validated['profil_public'] ?? true,
        ]);

        return response()->json([
            'message' => 'Profil créé avec succès',
            'profil' => new ProfilEtudiantResource($profil->load('utilisateur')),
        ], 201);
    }

    /**
     * Mettre à jour le profil
     */
    public function update(UpdateProfilEtudiantRequest $request)
    {
        $profil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->firstOrFail();
        $validated = $request->validated();

        $profil->update($validated);
        $profil->touch('date_mise_a_jour');

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'profil' => new ProfilEtudiantResource($profil->fresh()->load('utilisateur', 'profilCompetences.competence')),
        ]);
    }

    /**
     * Supprimer le profil
     */
    public function destroy(Request $request)
    {
        $profil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->firstOrFail();
        $profil->delete();

        return response()->json([
            'message' => 'Profil supprimé avec succès',
        ]);
    }

    /**
     * Obtenir les compétences du profil
     */
    public function getCompetences(Request $request)
    {
        $profil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->firstOrFail();

        $competences = ProfilCompetence::with('competence')
                                      ->where('profil_etudiant_id', $profil->id)
                                      ->get();

        return ProfilCompetenceResource::collection($competences);
    }

    /**
     * Ajouter une compétence au profil
     */
    public function addCompetence(AddCompetenceRequest $request)
    {
        $profil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->firstOrFail();
        $validated = $request->validated();

        // Vérifier si la compétence existe déjà
        $existing = ProfilCompetence::where('profil_etudiant_id', $profil->id)
                                   ->where('competence_id', $validated['competence_id'])
                                   ->first();

        if ($existing) {
            return response()->json(['message' => 'Cette compétence existe déjà dans votre profil'], 422);
        }

        $profilCompetence = ProfilCompetence::create([
            'profil_etudiant_id' => $profil->id,
            'competence_id' => $validated['competence_id'],
            'niveau' => $validated['niveau'] ?? null,
            'source' => $validated['source'] ?? 'manuel',
            'source_document_id' => $validated['source_document_id'] ?? null,
            'score_confiance' => $validated['score_confiance'] ?? null,
            'annees_experience' => $validated['annees_experience'] ?? null,
            'validee_par_etudiant' => $validated['validee_par_etudiant'] ?? false,
            'date_extraction' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Compétence ajoutée au profil avec succès',
            'competence' => new ProfilCompetenceResource($profilCompetence->load('competence')),
        ], 201);
    }

    /**
     * Mettre à jour une compétence du profil
     */
    public function updateCompetence(UpdateCompetenceRequest $request, $competenceId)
    {
        $profil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->firstOrFail();

        $profilCompetence = ProfilCompetence::where('profil_etudiant_id', $profil->id)
                                           ->where('competence_id', $competenceId)
                                           ->firstOrFail();

        $validated = $request->validated();
        $profilCompetence->update($validated);

        if (isset($validated['validee_par_etudiant']) && $validated['validee_par_etudiant']) {
            $profilCompetence->update(['date_validation' => Carbon::now()]);
        }

        return response()->json([
            'message' => 'Compétence mise à jour avec succès',
            'competence' => new ProfilCompetenceResource($profilCompetence->fresh()->load('competence')),
        ]);
    }

    /**
     * Supprimer une compétence du profil
     */
    public function removeCompetence(Request $request, $competenceId)
    {
        $profil = ProfilEtudiant::where('utilisateur_id', $request->user()->id)->firstOrFail();

        $profilCompetence = ProfilCompetence::where('profil_etudiant_id', $profil->id)
                                           ->where('competence_id', $competenceId)
                                           ->firstOrFail();

        $profilCompetence->delete();

        return response()->json([
            'message' => 'Compétence supprimée du profil avec succès',
        ]);
    }
}

