<?php

namespace App\Services;

use App\Models\Offre;
use App\Models\Matching;
use App\Models\ProfilEtudiant;
use App\Models\ProfilCompetence;
use App\Models\OffreCompetence;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MatchingService
{
    protected $algorithmeVersion = '1.0';

    /**
     * Calculer les matchings pour toutes les offres ou une offre spécifique
     */
    public function calculateMatchings($offreId = null, $force = false)
    {
        $stats = [
            'offres_traitees' => 0,
            'matchings_crees' => 0,
            'matchings_mis_a_jour' => 0,
            'erreurs' => 0,
        ];

        DB::beginTransaction();
        try {
            $query = Offre::where('statut', 'PUBLIEE');

            if ($offreId) {
                $query->where('id', $offreId);
            }

            $offres = $query->get();

            foreach ($offres as $offre) {
                $result = $this->calculateMatchingsForOffre($offre, $force);
                $stats['offres_traitees']++;
                $stats['matchings_crees'] += $result['crees'];
                $stats['matchings_mis_a_jour'] += $result['mis_a_jour'];
            }

            DB::commit();

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            $stats['erreurs']++;
            throw $e;
        }
    }

    /**
     * Calculer les matchings pour une offre spécifique
     */
    protected function calculateMatchingsForOffre(Offre $offre, $force = false)
    {
        $result = [
            'crees' => 0,
            'mis_a_jour' => 0,
        ];

        // Obtenir toutes les compétences de l'offre avec leurs poids
        $offreCompetences = OffreCompetence::where('offre_id', $offre->id)->get();

        if ($offreCompetences->isEmpty()) {
            return $result; // Pas de compétences dans l'offre
        }

        // Obtenir tous les profils étudiants actifs
        $profils = ProfilEtudiant::with(['profilCompetences.competence', 'utilisateur'])
                                 ->where('profil_public', true)
                                 ->whereHas('utilisateur', function ($q) {
                                     $q->where('is_active', true)
                                       ->where('role', 'etudiant');
                                 })
                                 ->get();

        foreach ($profils as $profil) {
            $matchResult = $this->calculateMatching($offre, $profil, $offreCompetences);

            // Trouver ou créer le matching
            $matching = Matching::firstOrNew([
                'offre_id' => $offre->id,
                'etudiant_id' => $profil->utilisateur_id,
            ]);

            // Ne mettre à jour que si le score a changé ou si c'est forcé
            if (!$matching->exists || $force || $matchResult['score_global'] !== $matching->score_global) {
                $matching->fill([
                    'score_global' => $matchResult['score_global'],
                    'score_competences' => $matchResult['score_competences'],
                    'score_localisation' => $matchResult['score_localisation'],
                    'score_experience' => $matchResult['score_experience'],
                    'competences_matchees' => $matchResult['competences_matchees'],
                    'competences_manquantes' => $matchResult['competences_manquantes'],
                    'points_forts' => $matchResult['points_forts'],
                    'points_amelioration' => $matchResult['points_amelioration'],
                    'algorithme_version' => $this->algorithmeVersion,
                    'seuil_notification' => 90.0, // Seuil par défaut (90% pour notifications)
                ]);

                // Notifier si le score dépasse 90% et n'était pas déjà notifié
                if ($matchResult['score_global'] >= 90 && !$matching->notifie) {
                    $matching->notifie = true;
                    $matching->date_notification = Carbon::now();
                    
                    // Envoyer une notification à l'étudiant
                    try {
                        $notificationService = new NotificationService();
                        $entrepriseNom = $offre->recruteur?->entreprise?->nom ?? 'Une entreprise';
                        
                        $notificationService->matchingEleve(
                            $profil->utilisateur_id,
                            $offre->titre,
                            $entrepriseNom,
                            (int) $matchResult['score_global'],
                            $offre->id
                        );
                    } catch (\Exception $e) {
                        // Log l'erreur mais ne bloque pas le processus
                        \Log::warning("Erreur envoi notification matching: " . $e->getMessage());
                    }
                }

                $matching->save();

                if ($matching->wasRecentlyCreated) {
                    $result['crees']++;
                } else {
                    $result['mis_a_jour']++;
                }
            }
        }

        return $result;
    }

    /**
     * Calculer le matching entre une offre et un profil
     */
    protected function calculateMatching(Offre $offre, ProfilEtudiant $profil, $offreCompetences)
    {
        $profilCompetences = ProfilCompetence::where('profil_etudiant_id', $profil->id)
                                            ->with('competence')
                                            ->get()
                                            ->keyBy('competence_id');

        // Calcul du score de compétences
        $scoreCompetences = $this->calculateCompetenceScore($offreCompetences, $profilCompetences);

        // Calcul du score de localisation
        $scoreLocalisation = $this->calculateLocationScore($offre, $profil);

        // Calcul du score d'expérience
        $scoreExperience = $this->calculateExperienceScore($offre, $profil);

        // Calcul du score global (pondération)
        $scoreGlobal = ($scoreCompetences * 0.6) + ($scoreLocalisation * 0.2) + ($scoreExperience * 0.2);

        // Analyser les compétences
        $competencesMatchees = [];
        $competencesManquantes = [];
        $pointsForts = [];
        $pointsAmelioration = [];

        foreach ($offreCompetences as $offreComp) {
            $competenceId = $offreComp->competence_id;
            
            if (isset($profilCompetences[$competenceId])) {
                $profilComp = $profilCompetences[$competenceId];
                $competencesMatchees[] = [
                    'competence_id' => $competenceId,
                    'nom' => $profilComp->competence->nom,
                    'niveau_requis' => $offreComp->niveau_requis,
                    'niveau_etudiant' => $profilComp->niveau,
                    'importance' => $offreComp->importance,
                ];
            } else {
                $competencesManquantes[] = [
                    'competence_id' => $competenceId,
                    'importance' => $offreComp->importance,
                ];
            }
        }

        // Points forts et points d'amélioration
        if ($scoreCompetences >= 80) {
            $pointsForts[] = 'Excellent alignement des compétences';
        }
        if ($scoreLocalisation >= 80) {
            $pointsForts[] = 'Localisation compatible';
        }
        if (count($competencesManquantes) === 0) {
            $pointsForts[] = 'Toutes les compétences requises sont présentes';
        }

        if (count($competencesManquantes) > 0) {
            $pointsAmelioration[] = 'Certaines compétences requises sont manquantes';
        }
        if ($scoreExperience < 50) {
            $pointsAmelioration[] = 'Expérience inférieure aux attentes';
        }

        return [
            'score_global' => round($scoreGlobal, 2),
            'score_competences' => round($scoreCompetences, 2),
            'score_localisation' => round($scoreLocalisation, 2),
            'score_experience' => round($scoreExperience, 2),
            'competences_matchees' => $competencesMatchees,
            'competences_manquantes' => $competencesManquantes,
            'points_forts' => $pointsForts,
            'points_amelioration' => $pointsAmelioration,
        ];
    }

    /**
     * Calculer le score de compétences
     */
    protected function calculateCompetenceScore($offreCompetences, $profilCompetences)
    {
        $totalPoints = 0;
        $totalPoids = 0;

        foreach ($offreCompetences as $offreComp) {
            $poids = $offreComp->poids;
            $totalPoids += $poids;

            if (isset($profilCompetences[$offreComp->competence_id])) {
                $profilComp = $profilCompetences[$offreComp->competence_id];
                $points = $this->compareNiveaux($offreComp->niveau_requis, $profilComp->niveau, $offreComp->importance);
                $totalPoints += $points * $poids;
            }
        }

        if ($totalPoids === 0) {
            return 0;
        }

        return ($totalPoints / $totalPoids) * 100;
    }

    /**
     * Comparer deux niveaux de compétence
     */
    protected function compareNiveaux($niveauRequis, $niveauEtudiant, $importance)
    {
        $niveaux = ['debutant' => 1, 'intermediaire' => 2, 'avance' => 3, 'expert' => 4];
        
        $niveauReq = $niveaux[$niveauRequis] ?? 1;
        $niveauEtud = $niveaux[$niveauEtudiant] ?? 1;

        if ($niveauEtud >= $niveauReq) {
            return 100; // Niveau suffisant
        } elseif ($importance === 'indispensable') {
            return 0; // Inacceptable si indispensable
        } else {
            // Score proportionnel pour les compétences moins importantes
            return ($niveauEtud / $niveauReq) * 100;
        }
    }

    /**
     * Calculer le score de localisation
     */
    protected function calculateLocationScore(Offre $offre, ProfilEtudiant $profil)
    {
        // Si télétravail total, score parfait
        if ($offre->teletravail === 'total') {
            return 100;
        }

        // Si localisation souhaitée correspond
        $localisationsSouhaitees = $profil->localisation_souhaitee ?? [];
        if (in_array($offre->lieu, $localisationsSouhaitees)) {
            return 100;
        }

        // Si même ville/pays
        $lieuOffre = strtolower($offre->lieu);
        $localisationActuelle = strtolower($profil->localisation_actuelle ?? '');

        if (stripos($lieuOffre, $localisationActuelle) !== false || stripos($localisationActuelle, $lieuOffre) !== false) {
            return 80;
        }

        // Si mobilité acceptée
        if ($profil->mobilite === 'nationale' || $profil->mobilite === 'internationale') {
            return 60;
        }

        return 40; // Score par défaut
    }

    /**
     * Calculer le score d'expérience
     */
    protected function calculateExperienceScore(Offre $offre, ProfilEtudiant $profil)
    {
        $experienceRequise = $offre->annees_experience_min ?? 0;

        if ($experienceRequise === 0) {
            return 100; // Pas d'exigence d'expérience
        }

        // Calculer l'expérience totale du profil (basée sur les compétences)
        $profilCompetences = ProfilCompetence::where('profil_etudiant_id', $profil->id)->get();
        $experienceTotale = $profilCompetences->sum('annees_experience') ?? 0;

        if ($experienceTotale >= $experienceRequise) {
            return 100;
        } else {
            // Score proportionnel
            return ($experienceTotale / $experienceRequise) * 100;
        }
    }
}

