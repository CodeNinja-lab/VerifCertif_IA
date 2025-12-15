<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use App\Models\VerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Statistiques pour le dashboard université
     */
    public function universityStats(Request $request)
    {
        $user = $request->user();
        
        // Récupérer l'administration de l'utilisateur
        $administration = \App\Models\Administration::where('email_contact', $user->email)->first();
        
        if (!$administration) {
            return response()->json(['message' => 'Administration non trouvée'], 404);
        }

        // Nombre total d'étudiants
        $totalStudents = User::where('role', 'etudiant')->count();
        
        // Étudiants du mois dernier pour le pourcentage
        $lastMonthStudents = User::where('role', 'etudiant')
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->count();
        $studentGrowth = $totalStudents > 0 ? round(($lastMonthStudents / $totalStudents) * 100, 1) : 0;

        // Nombre total de diplômes délivrés par cette administration
        $totalDegrees = Document::where('administration_id', $administration->id)->count();
        
        // Diplômes du mois dernier
        $lastMonthDegrees = Document::where('administration_id', $administration->id)
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->count();
        $degreeGrowth = $totalDegrees > 0 ? round(($lastMonthDegrees / $totalDegrees) * 100, 1) : 0;

        // Nombre de certifications blockchain (documents avec tx_hash)
        $totalCertifications = Document::where('administration_id', $administration->id)
            ->whereNotNull('blockchain_tx_hash')
            ->count();
        
        $lastMonthCertifications = Document::where('administration_id', $administration->id)
            ->whereNotNull('blockchain_tx_hash')
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->count();
        $certificationGrowth = $totalCertifications > 0 ? round(($lastMonthCertifications / $totalCertifications) * 100, 1) : 0;

        // Nombre de vérifications
        $totalVerifications = VerificationLog::whereHas('document', function($query) use ($administration) {
            $query->where('administration_id', $administration->id);
        })->count();
        
        $lastMonthVerifications = VerificationLog::whereHas('document', function($query) use ($administration) {
            $query->where('administration_id', $administration->id);
        })->where('created_at', '>=', Carbon::now()->subMonth())->count();
        $verificationGrowth = $totalVerifications > 0 ? round(($lastMonthVerifications / $totalVerifications) * 100, 1) : 0;

        return response()->json([
            'students' => [
                'total' => $totalStudents,
                'growth' => "+{$studentGrowth}%",
            ],
            'degrees' => [
                'total' => $totalDegrees,
                'growth' => "+{$degreeGrowth}%",
            ],
            'certifications' => [
                'total' => $totalCertifications,
                'growth' => "+{$certificationGrowth}%",
            ],
            'verifications' => [
                'total' => $totalVerifications,
                'growth' => "+{$verificationGrowth}%",
            ],
        ]);
    }

    /**
     * Diplômes récents
     */
    public function recentDegrees(Request $request)
    {
        $user = $request->user();
        $administration = \App\Models\Administration::where('email_contact', $user->email)->first();
        
        if (!$administration) {
            return response()->json(['message' => 'Administration non trouvée'], 404);
        }

        $degrees = Document::with(['etudiant'])
            ->where('administration_id', $administration->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($doc) {
                $dateEmission = $doc->date_emission ? Carbon::parse($doc->date_emission)->format('Y-m-d') : $doc->created_at->format('Y-m-d');
                
                return [
                    'id' => $doc->id,
                    'student' => ($doc->etudiant->prenom ?? '') . ' ' . ($doc->etudiant->nom ?? ''),
                    'degree' => $doc->titre,
                    'date' => $dateEmission,
                    'status' => $doc->statut === 'VALIDE' ? 'certified' : 'pending',
                ];
            });

        return response()->json($degrees);
    }

    /**
     * Vérifications récentes
     */
    public function recentVerifications(Request $request)
    {
        $user = $request->user();
        $administration = \App\Models\Administration::where('email_contact', $user->email)->first();
        
        if (!$administration) {
            return response()->json(['message' => 'Administration non trouvée'], 404);
        }

        $verifications = VerificationLog::with(['document.etudiant'])
            ->whereHas('document', function($query) use ($administration) {
                $query->where('administration_id', $administration->id);
            })
            ->where('resultat', 'VALIDE')
            ->orderBy('date_verification', 'desc')
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'company' => $log->verificateur_type === 'recruteur' ? 'Recruteur' : 'Entreprise',
                    'student' => ($log->document->etudiant->prenom ?? '') . ' ' . ($log->document->etudiant->nom ?? ''),
                    'date' => $log->date_verification->diffForHumans(),
                    'status' => 'verified',
                ];
            });

        return response()->json($verifications);
    }

    /**
     * Statistiques des étudiants
     */
    public function studentsStats(Request $request)
    {
        $totalStudents = User::where('role', 'etudiant')->count();
        $activeStudents = User::where('role', 'etudiant')->where('is_active', true)->count();
        $graduatedStudents = User::where('role', 'etudiant')
            ->whereHas('documents', function($query) {
                $query->where('statut', 'VALIDE');
            })->count();
        $newThisMonth = User::where('role', 'etudiant')
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->count();

        return response()->json([
            'data' => [
                'total' => $totalStudents,
                'active' => $activeStudents,
                'graduated' => $graduatedStudents,
                'new_this_month' => $newThisMonth,
            ]
        ]);
    }

    /**
     * Liste des étudiants
     */
    public function studentsList(Request $request)
    {
        $query = User::where('role', 'etudiant')->withCount('documents');

        // Filtrage par recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filtrage par statut
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'graduated') {
                $query->whereHas('documents', function($q) {
                    $q->where('statut', 'VALIDE');
                });
            }
        }

        $students = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $students->getCollection()->transform(function($student) {
            return [
                'id' => $student->id,
                'nom' => $student->nom,
                'prenom' => $student->prenom,
                'email' => $student->email,
                'telephone' => $student->telephone,
                'status' => $student->is_active ? 'active' : 'inactive',
                'created_at' => $student->created_at->toISOString(),
                'degrees_count' => $student->documents_count,
            ];
        });

        return response()->json(['data' => $students]);
    }
}
