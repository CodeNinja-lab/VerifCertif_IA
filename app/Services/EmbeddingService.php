<?php

namespace App\Services;

use App\Models\CandidateEmbedding;
use App\Models\JobOfferEmbedding;
use App\Models\ProfilEtudiant;
use App\Models\ProfilCompetence;
use App\Models\Offre;
use App\Models\OffreCompetence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Construire le texte du profil candidat pour l'embedding
     * Règle: 75% compétences certifiées si disponibles, sinon 100% compétences déclarées
     */
    public function buildCandidateProfileText(int $userId): ?string
    {
        $user = User::with([
            'profilEtudiant.profilCompetences.competence',
            'profilEtudiant.profilCompetences.sourceDocument',
        ])->find($userId);

        if (!$user || !$user->profilEtudiant) {
            return null;
        }

        $profil = $user->profilEtudiant;
        $competences = $profil->profilCompetences;

        // Séparer compétences certifiées et non certifiées
        $certifiedSkills = [];
        $uncertifiedSkills = [];

        foreach ($competences as $profilComp) {
            $skillText = $profilComp->competence->nom;
            $skillText .= " (niveau: {$profilComp->niveau})";
            
            if ($profilComp->annees_experience) {
                $skillText .= " - {$profilComp->annees_experience} ans d'expérience";
            }

            // Compétence certifiée = issue d'un document validé
            if ($profilComp->source === 'document' && $profilComp->source_document_id && $profilComp->validee_par_etudiant) {
                $certifiedSkills[] = $skillText;
            } else {
                $uncertifiedSkills[] = $skillText;
            }
        }

        // Construire le profil avec la règle des 75%
        $profileParts = [];
        
        // Nom complet
        $profileParts[] = "Candidat: {$user->prenom} {$user->nom}";

        // Bio
        if ($profil->bio) {
            $profileParts[] = "Profil: {$profil->bio}";
        }

        // Compétences certifiées (prioritaires)
        if (!empty($certifiedSkills)) {
            $profileParts[] = "Compétences certifiées: " . implode(', ', $certifiedSkills);
            $profileParts[] = "✓ Profil vérifié par blockchain";
        }

        // Compétences non certifiées (maximum 25% du poids si certifications existent)
        if (!empty($uncertifiedSkills)) {
            if (empty($certifiedSkills)) {
                // Pas de diplôme certifié: utiliser toutes les compétences déclarées
                $profileParts[] = "Compétences déclarées: " . implode(', ', $uncertifiedSkills);
                $profileParts[] = "⚠ Profil non certifié";
            } else {
                // Limiter les compétences non certifiées pour respecter le ratio 75/25
                $maxUncertified = (int) (count($certifiedSkills) / 3);
                $limitedUncertified = array_slice($uncertifiedSkills, 0, $maxUncertified);
                if (!empty($limitedUncertified)) {
                    $profileParts[] = "Autres compétences: " . implode(', ', $limitedUncertified);
                }
            }
        }

        // Localisation
        if ($profil->localisation_actuelle) {
            $profileParts[] = "Localisation: {$profil->localisation_actuelle}";
        }

        // Disponibilité
        if ($profil->disponibilite) {
            $profileParts[] = "Disponibilité: {$profil->disponibilite}";
        }

        return implode(". ", $profileParts);
    }

    /**
     * Construire le texte de l'offre pour l'embedding
     */
    public function buildJobOfferText(int $offreId): ?string
    {
        $offre = Offre::with(['offreCompetences.competence', 'recruteur'])->find($offreId);

        if (!$offre) {
            return null;
        }

        $offerParts = [];

        // Titre
        $offerParts[] = "Poste: {$offre->titre}";

        // Entreprise
        if ($offre->entreprise) {
            $offerParts[] = "Entreprise: {$offre->entreprise}";
        }

        // Description
        if ($offre->description) {
            $offerParts[] = "Description: {$offre->description}";
        }

        // Missions principales
        if ($offre->missions_principales) {
            $offerParts[] = "Missions: {$offre->missions_principales}";
        }

        // Profil recherché
        if ($offre->profil_recherche) {
            $offerParts[] = "Profil recherché: {$offre->profil_recherche}";
        }

        // Compétences requises
        if ($offre->offreCompetences->isNotEmpty()) {
            $skills = $offre->offreCompetences->map(function ($oc) {
                $skillText = $oc->competence->nom;
                $skillText .= " (niveau {$oc->niveau_requis}, importance: {$oc->importance})";
                return $skillText;
            })->toArray();
            $offerParts[] = "Compétences requises: " . implode(', ', $skills);
        }

        // Nice to have
        if ($offre->nice_to_have) {
            $offerParts[] = "Atouts supplémentaires: {$offre->nice_to_have}";
        }

        // Lieu
        if ($offre->lieu) {
            $offerParts[] = "Lieu: {$offre->lieu}";
        }

        // Type de contrat
        if ($offre->type_contrat) {
            $offerParts[] = "Type de contrat: {$offre->type_contrat}";
        }

        // Expérience
        if ($offre->annees_experience_min) {
            $offerParts[] = "Expérience minimale: {$offre->annees_experience_min} ans";
        }

        return implode(". ", $offerParts);
    }

    /**
     * Générer et sauvegarder l'embedding d'un candidat
     */
    public function generateCandidateEmbedding(int $userId): bool
    {
        try {
            $profileText = $this->buildCandidateProfileText($userId);

            if (!$profileText) {
                Log::warning("Cannot build profile text for user {$userId}");
                return false;
            }

            $result = $this->aiService->generateEmbedding($profileText);

            if (!$result || !isset($result['embedding'])) {
                Log::error("Failed to generate embedding for user {$userId}");
                return false;
            }

            $user = User::find($userId);
            $fullname = "{$user->prenom} {$user->nom}";

            // Upsert dans PostgreSQL
            CandidateEmbedding::updateOrCreate(
                ['id' => $userId],
                [
                    'fullname' => $fullname,
                    'profile_text' => $profileText,
                    'embedding' => $result['embedding'],
                ]
            );

            Log::info("Embedding generated for candidate {$userId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error generating candidate embedding", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Générer et sauvegarder l'embedding d'une offre
     */
    public function generateJobOfferEmbedding(int $offreId): bool
    {
        try {
            $offerText = $this->buildJobOfferText($offreId);

            if (!$offerText) {
                Log::warning("Cannot build offer text for offre {$offreId}");
                return false;
            }

            $result = $this->aiService->generateEmbedding($offerText);

            if (!$result || !isset($result['embedding'])) {
                Log::error("Failed to generate embedding for offre {$offreId}");
                return false;
            }

            $offre = Offre::find($offreId);

            // Upsert dans PostgreSQL
            JobOfferEmbedding::updateOrCreate(
                ['id' => $offreId],
                [
                    'title' => $offre->titre,
                    'description' => $offerText,
                    'embedding' => $result['embedding'],
                ]
            );

            Log::info("Embedding generated for job offer {$offreId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error generating job offer embedding", [
                'offre_id' => $offreId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Supprimer l'embedding d'une offre
     */
    public function deleteJobOfferEmbedding(int $offreId): bool
    {
        try {
            JobOfferEmbedding::where('id', $offreId)->delete();
            Log::info("Embedding deleted for job offer {$offreId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error deleting job offer embedding", [
                'offre_id' => $offreId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtenir les offres compatibles pour un candidat
     * Utilise la similarité cosinus via PostgreSQL
     */
    public function getCompatibleJobOffers(int $userId, int $limit = 20): array
    {
        try {
            $candidate = CandidateEmbedding::find($userId);

            if (!$candidate || !$candidate->embedding) {
                return [];
            }

            $embeddingString = '[' . implode(',', $candidate->embedding) . ']';

            // Requête cosine similarity avec pgvector
            $results = DB::connection('pgsql')
                ->select("
                    SELECT
                        j.id,
                        j.title,
                        1 - (j.embedding <=> ?::vector) AS similarity
                    FROM job_offers j
                    ORDER BY similarity DESC
                    LIMIT ?
                ", [$embeddingString, $limit]);

            return array_map(function ($row) {
                return [
                    'offre_id' => $row->id,
                    'title' => $row->title,
                    'similarity_score' => round($row->similarity * 100, 2), // Convertir en pourcentage
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error("Error getting compatible job offers", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
