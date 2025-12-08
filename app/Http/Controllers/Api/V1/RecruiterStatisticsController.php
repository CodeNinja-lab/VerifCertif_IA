<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Offre;
use App\Models\Matching;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecruiterStatisticsController extends Controller
{
    /**
     * Statistiques du dashboard recruteur
     */
    public function dashboard(Request $request)
    {
        $recruteurId = $request->user()->id;
        
        // Période par défaut (30 derniers jours)
        $periode = $request->get('periode', 30); // jours
        $dateDebut = Carbon::now()->subDays($periode);

        // Offres actives
        $offresActives = Offre::where('recruteur_id', $recruteurId)
                             ->where('statut', 'PUBLIEE')
                             ->count();

        // Total candidatures reçues (via matchings)
        $candidaturesTotal = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })->count();

        // Nouvelles candidatures ce mois
        $candidaturesCeMois = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })
        ->whereMonth('date_matching', now()->month)
        ->whereYear('date_matching', now()->year)
        ->count();

        // Total vues profil (somme des vues de toutes les offres)
        $vuesTotal = Offre::where('recruteur_id', $recruteurId)
                         ->sum('nombre_vues');

        // Recrutements réussis (matchings avec intérêt)
        $recrutementsReussis = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })
        ->where('interesse', true)
        ->count();

        // Offres en cours avec détails
        $offresEnCours = Offre::where('recruteur_id', $recruteurId)
                             ->where('statut', 'PUBLIEE')
                             ->withCount(['matchings as total_candidatures'])
                             ->orderBy('date_publication', 'desc')
                             ->limit(5)
                             ->get()
                             ->map(function($offre) {
                                 return [
                                     'id' => $offre->id,
                                     'titre' => $offre->titre,
                                     'date_publication' => $offre->date_publication?->diffForHumans(),
                                     'candidatures' => $offre->total_candidatures,
                                     'vues' => $offre->nombre_vues,
                                     'nouvelles_candidatures' => Matching::where('offre_id', $offre->id)
                                                                        ->whereMonth('date_matching', now()->month)
                                                                        ->whereYear('date_matching', now()->year)
                                                                        ->count(),
                                 ];
                             });

        // Candidatures récentes
        $candidaturesRecentes = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })
        ->with(['etudiant.profilEtudiant', 'offre'])
        ->orderBy('date_matching', 'desc')
        ->limit(5)
        ->get()
        ->map(function($matching) {
            return [
                'id' => $matching->id,
                'candidat' => $matching->etudiant ? [
                    'id' => $matching->etudiant->id,
                    'name' => trim(($matching->etudiant->prenom ?? '') . ' ' . ($matching->etudiant->nom ?? '')),
                    'email' => $matching->etudiant->email,
                ] : null,
                'offre' => $matching->offre ? [
                    'id' => $matching->offre->id,
                    'titre' => $matching->offre->titre,
                ] : null,
                'match_score' => $matching->score_global,
                'date' => $matching->date_matching?->diffForHumans(),
            ];
        });

        // Performance
        $tauxReponse = 0;
        $delaiMoyen = 0;
        if ($candidaturesTotal > 0) {
            $matchingsAvecVue = Matching::whereHas('offre', function($query) use ($recruteurId) {
                $query->where('recruteur_id', $recruteurId);
            })
            ->where('vu_par_recruteur', true)
            ->count();
            $tauxReponse = round(($matchingsAvecVue / $candidaturesTotal) * 100, 1);
        }

        $stats = [
            'offres_actives' => $offresActives,
            'candidatures_total' => $candidaturesTotal,
            'candidatures_ce_mois' => $candidaturesCeMois,
            'vues_total' => $vuesTotal,
            'recrutements_reussis' => $recrutementsReussis,
            'offres_en_cours' => $offresEnCours,
            'candidatures_recentes' => $candidaturesRecentes,
            'performance' => [
                'taux_reponse' => $tauxReponse,
                'delai_moyen' => $delaiMoyen,
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Statistiques des offres
     */
    public function offres(Request $request)
    {
        $recruteurId = $request->user()->id;
        $periode = $request->get('periode', 30);
        $dateDebut = Carbon::now()->subDays($periode);

        // Statistiques par statut
        $parStatut = Offre::where('recruteur_id', $recruteurId)
                          ->select('statut', DB::raw('count(*) as total'))
                          ->groupBy('statut')
                          ->pluck('total', 'statut');

        // Évolution des offres publiées avec vues
        $evolutionOffres = Offre::where('recruteur_id', $recruteurId)
                         ->where('statut', 'PUBLIEE')
                         ->where('date_publication', '>=', $dateDebut)
                         ->select(
                             DB::raw("DATE(date_publication) as date"), 
                             DB::raw('count(*) as total'),
                             DB::raw('sum(nombre_vues) as total_vues')
                         )
                         ->groupBy(DB::raw('DATE(date_publication)'))
                         ->orderBy('date', 'asc')
                         ->get();

        // Évolution des candidatures par date de matching
        $evolutionCandidatures = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })
        ->where('date_matching', '>=', $dateDebut)
        ->select(
            DB::raw("DATE(date_matching) as date"),
            DB::raw('count(*) as total_candidatures')
        )
        ->groupBy(DB::raw('DATE(date_matching)'))
        ->orderBy('date', 'asc')
        ->get()
        ->keyBy('date');

        // Fusionner les données
        $evolution = $evolutionOffres->map(function($item) use ($evolutionCandidatures) {
            $candidatures = $evolutionCandidatures->get($item->date);
            return [
                'date' => $item->date,
                'total' => $item->total,
                'total_vues' => $item->total_vues ?? 0,
                'candidatures' => $candidatures ? $candidatures->total_candidatures : 0,
            ];
        });

        // Top offres par candidatures
        $topOffres = Offre::where('recruteur_id', $recruteurId)
                         ->withCount('matchings')
                         ->orderBy('matchings_count', 'desc')
                         ->limit(10)
                         ->get()
                         ->map(function($offre) {
                             return [
                                 'id' => $offre->id,
                                 'titre' => $offre->titre,
                                 'candidatures' => $offre->matchings_count,
                                 'vues' => $offre->nombre_vues,
                             ];
                         });

        return response()->json([
            'par_statut' => $parStatut,
            'evolution' => $evolution,
            'top_offres' => $topOffres,
        ]);
    }

    /**
     * Statistiques des candidats
     */
    public function candidates(Request $request)
    {
        $recruteurId = $request->user()->id;

        // Candidats par score de matching
        $parTrancheScore = [
            '0-20' => Matching::whereHas('offre', function($query) use ($recruteurId) {
                $query->where('recruteur_id', $recruteurId);
            })->whereBetween('score_global', [0, 20])->count(),
            '21-40' => Matching::whereHas('offre', function($query) use ($recruteurId) {
                $query->where('recruteur_id', $recruteurId);
            })->whereBetween('score_global', [21, 40])->count(),
            '41-60' => Matching::whereHas('offre', function($query) use ($recruteurId) {
                $query->where('recruteur_id', $recruteurId);
            })->whereBetween('score_global', [41, 60])->count(),
            '61-80' => Matching::whereHas('offre', function($query) use ($recruteurId) {
                $query->where('recruteur_id', $recruteurId);
            })->whereBetween('score_global', [61, 80])->count(),
            '81-100' => Matching::whereHas('offre', function($query) use ($recruteurId) {
                $query->where('recruteur_id', $recruteurId);
            })->whereBetween('score_global', [81, 100])->count(),
        ];

        // Score moyen
        $scoreMoyen = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })->avg('score_global');

        // Candidats avec intérêt
        $avecInteret = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })->where('interesse', true)->count();

        // Top compétences (depuis les matchings)
        $matchings = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })->get();

        $competencesCount = [];
        foreach ($matchings as $matching) {
            if ($matching->competences_matchees && is_array($matching->competences_matchees)) {
                foreach ($matching->competences_matchees as $competence) {
                    $nom = is_array($competence) ? ($competence['nom'] ?? $competence['name'] ?? '') : $competence;
                    if ($nom) {
                        $competencesCount[$nom] = ($competencesCount[$nom] ?? 0) + 1;
                    }
                }
            }
        }
        arsort($competencesCount);
        $topSkills = array_slice(array_map(function($nom, $count) {
            return ['nom' => $nom, 'total' => $count];
        }, array_keys($competencesCount), $competencesCount), 0, 10);

        // Statistiques d'expérience
        $matchingsWithProfil = Matching::whereHas('offre', function($query) use ($recruteurId) {
            $query->where('recruteur_id', $recruteurId);
        })
        ->with('etudiant.profilEtudiant')
        ->get();

        $experienceStats = [
            'junior' => 0,      // 0-2 ans
            'intermediaire' => 0, // 3-5 ans
            'senior' => 0,      // 5+ ans
        ];

        $localisationStats = [];
        $totalWithProfil = 0;

        foreach ($matchingsWithProfil as $matching) {
            if ($matching->etudiant && $matching->etudiant->profilEtudiant) {
                $profil = $matching->etudiant->profilEtudiant;
                $anneesExp = $profil->annees_experience ?? 0;
                
                if ($anneesExp <= 2) {
                    $experienceStats['junior']++;
                } elseif ($anneesExp <= 5) {
                    $experienceStats['intermediaire']++;
                } else {
                    $experienceStats['senior']++;
                }

                if ($profil->localisation) {
                    $localisationStats[$profil->localisation] = ($localisationStats[$profil->localisation] ?? 0) + 1;
                }
                $totalWithProfil++;
            }
        }

        // Calculer les pourcentages d'expérience
        $experiencePercentages = [];
        if ($totalWithProfil > 0) {
            $experiencePercentages = [
                'junior' => round(($experienceStats['junior'] / $totalWithProfil) * 100, 1),
                'intermediaire' => round(($experienceStats['intermediaire'] / $totalWithProfil) * 100, 1),
                'senior' => round(($experienceStats['senior'] / $totalWithProfil) * 100, 1),
            ];
        }

        // Top localisations
        arsort($localisationStats);
        $topLocalisations = array_slice(array_map(function($loc, $count) use ($totalWithProfil) {
            return [
                'localisation' => $loc,
                'count' => $count,
                'percentage' => $totalWithProfil > 0 ? round(($count / $totalWithProfil) * 100, 1) : 0
            ];
        }, array_keys($localisationStats), $localisationStats), 0, 5);

        return response()->json([
            'par_tranche_score' => $parTrancheScore,
            'score_moyen' => round($scoreMoyen ?? 0, 2),
            'avec_interet' => $avecInteret,
            'top_skills' => $topSkills,
            'experience_stats' => $experienceStats,
            'experience_percentages' => $experiencePercentages,
            'localisation_stats' => $topLocalisations,
        ]);
    }
}

