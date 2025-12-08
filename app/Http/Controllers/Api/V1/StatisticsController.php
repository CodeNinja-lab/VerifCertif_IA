<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use App\Models\Offre;
use App\Models\Matching;
use App\Models\Administration;
use App\Models\Competence;
use App\Models\VerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Statistiques du dashboard avec tous les indicateurs demandés
     */
    public function dashboard(Request $request)
    {
        // Période par défaut (12 derniers mois)
        $periode = $request->get('periode', 12); // mois
        $dateDebut = Carbon::now()->subMonths($periode);

        // Documents certifiés par période (PostgreSQL utilise TO_CHAR)
        $documentsParPeriode = Document::select(DB::raw("TO_CHAR(date_certification, 'YYYY-MM') as periode"), DB::raw('count(*) as total'))
                                       ->where('date_certification', '>=', $dateDebut)
                                       ->groupBy('periode')
                                       ->orderBy('periode', 'asc')
                                       ->get();

        // Répartition des demandes (public/privé via verification_logs)
        $verificationsPublic = VerificationLog::where('verificateur_type', 'public')
                                             ->where('date_verification', '>=', $dateDebut)
                                             ->count();
        $verificationsPrive = VerificationLog::whereIn('verificateur_type', ['recruteur', 'administration'])
                                             ->where('date_verification', '>=', $dateDebut)
                                             ->count();

        // Pics d'activité (par mois et par jour de la semaine)
        $picsParMois = VerificationLog::select(DB::raw("TO_CHAR(date_verification, 'YYYY-MM') as mois"), DB::raw('count(*) as total'))
                                      ->where('date_verification', '>=', $dateDebut)
                                      ->groupBy('mois')
                                      ->orderBy('total', 'desc')
                                      ->limit(12)
                                      ->get();

        // Pics par jour de la semaine (PostgreSQL utilise EXTRACT)
        $picsParJourSemaine = VerificationLog::select(DB::raw('EXTRACT(DOW FROM date_verification) as jour_semaine'), DB::raw('count(*) as total'))
                                             ->where('date_verification', '>=', $dateDebut)
                                             ->groupBy('jour_semaine')
                                             ->orderBy('total', 'desc')
                                             ->get();

        // Compétences les plus demandées (via offre_competences)
        $competencesPlusDemandees = DB::table('offre_competences')
                                      ->join('competences', 'offre_competences.competence_id', '=', 'competences.id')
                                      ->select('competences.nom', 'competences.id', DB::raw('count(*) as total'))
                                      ->groupBy('competences.id', 'competences.nom')
                                      ->orderBy('total', 'desc')
                                      ->limit(10)
                                      ->get();

        // Diplômes les plus demandés (via documents et offres)
        $diplomesPlusDemandes = Document::select('type_document', 'titre', DB::raw('count(*) as total'))
                                       ->where('date_certification', '>=', $dateDebut)
                                       ->groupBy('type_document', 'titre')
                                       ->orderBy('total', 'desc')
                                       ->limit(10)
                                       ->get();

        $stats = [
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => Carbon::now()->format('Y-m-d'),
                'mois' => $periode,
            ],
            'documents' => [
                'total' => Document::count(),
                'actifs' => Document::where('statut', 'ACTIF')->count(),
                'revokes' => Document::where('statut', 'REVOQUE')->count(),
                'expires' => Document::where('statut', 'EXPIRE')->count(),
                'par_type' => Document::select('type_document', DB::raw('count(*) as total'))
                                     ->groupBy('type_document')
                                     ->pluck('total', 'type_document'),
                'certifies_par_periode' => $documentsParPeriode,
                'total_certifies_periode' => Document::where('date_certification', '>=', $dateDebut)->count(),
            ],
            'verifications' => [
                'total_periode' => VerificationLog::where('date_verification', '>=', $dateDebut)->count(),
                'repartition' => [
                    'public' => $verificationsPublic,
                    'prive' => $verificationsPrive,
                    'pourcentage_public' => $verificationsPublic + $verificationsPrive > 0 
                        ? round(($verificationsPublic / ($verificationsPublic + $verificationsPrive)) * 100, 2) 
                        : 0,
                ],
                'pics_activite' => [
                    'par_mois' => $picsParMois,
                    'par_jour_semaine' => $picsParJourSemaine,
                ],
                'moyenne_par_jour' => VerificationLog::where('date_verification', '>=', $dateDebut)
                                                     ->selectRaw("DATE(date_verification) as date, COUNT(*) as total")
                                                     ->groupBy(DB::raw('DATE(date_verification)'))
                                                     ->get()
                                                     ->avg('total'),
            ],
            'competences' => [
                'plus_demandees' => $competencesPlusDemandees,
                'total_unique' => Competence::count(),
            ],
            'diplomes' => [
                'plus_demandes' => $diplomesPlusDemandes,
            ],
            'users' => [
                'total' => User::count(),
                'actifs' => User::where('is_active', true)->count(),
                'par_role' => User::select('role', DB::raw('count(*) as total'))
                                 ->groupBy('role')
                                 ->pluck('total', 'role'),
            ],
            'offres' => [
                'total' => Offre::count(),
                'publiees' => Offre::where('statut', 'PUBLIEE')->count(),
                'par_statut' => Offre::select('statut', DB::raw('count(*) as total'))
                                   ->groupBy('statut')
                                   ->pluck('total', 'statut'),
            ],
            'matchings' => [
                'total' => Matching::count(),
                'avec_interet' => Matching::where('interesse', true)->count(),
            ],
            'administrations' => [
                'total' => Administration::count(),
                'verifiees' => Administration::where('statut', 'verifie')->count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Statistiques sur les documents
     */
    public function documents(Request $request)
    {
        $stats = [
            'total' => Document::count(),
            'par_statut' => Document::select('statut', DB::raw('count(*) as total'))
                                   ->groupBy('statut')
                                   ->pluck('total', 'statut'),
            'par_type' => Document::select('type_document', DB::raw('count(*) as total'))
                                 ->groupBy('type_document')
                                 ->pluck('total', 'type_document'),
            'par_mois' => Document::select(DB::raw("TO_CHAR(date_certification, 'YYYY-MM') as mois"), DB::raw('count(*) as total'))
                                 ->groupBy('mois')
                                 ->orderBy('mois', 'desc')
                                 ->limit(12)
                                 ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Statistiques sur les matchings
     */
    public function matchings(Request $request)
    {
        $stats = [
            'total' => Matching::count(),
            'score_moyen' => Matching::avg('score_global'),
            'score_max' => Matching::max('score_global'),
            'score_min' => Matching::min('score_global'),
            'avec_interet' => Matching::where('interesse', true)->count(),
            'par_tranche_score' => [
                '0-20' => Matching::whereBetween('score_global', [0, 20])->count(),
                '21-40' => Matching::whereBetween('score_global', [21, 40])->count(),
                '41-60' => Matching::whereBetween('score_global', [41, 60])->count(),
                '61-80' => Matching::whereBetween('score_global', [61, 80])->count(),
                '81-100' => Matching::whereBetween('score_global', [81, 100])->count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Statistiques sur les utilisateurs
     */
    public function users(Request $request)
    {
        $stats = [
            'total' => User::count(),
            'actifs' => User::where('is_active', true)->count(),
            'inactifs' => User::where('is_active', false)->count(),
            'par_role' => User::select('role', DB::raw('count(*) as total'))
                             ->groupBy('role')
                             ->pluck('total', 'role'),
            'avec_profil' => User::whereHas('profilEtudiant')->count(),
            'nouveaux_ce_mois' => User::whereMonth('date_creation', now()->month)
                                     ->whereYear('date_creation', now()->year)
                                     ->count(),
        ];

        return response()->json($stats);
    }
}

