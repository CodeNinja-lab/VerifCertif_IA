<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDocumentRequest;
use App\Http\Requests\Api\V1\UpdateDocumentRequest;
use App\Http\Requests\Api\V1\VerifyDocumentRequest;
use App\Http\Requests\Api\V1\RevokeDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Models\VerificationLog;
use App\Models\Revocation;
use App\Models\AuditLog;
use App\Services\DocumentVerificationService;
use App\Services\DocumentEmissionService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentController extends Controller
{
    protected $verificationService;
    protected $emissionService;
    protected $qrCodeService;

    public function __construct(
        DocumentVerificationService $verificationService,
        DocumentEmissionService $emissionService,
        QrCodeService $qrCodeService
    ) {
        $this->verificationService = $verificationService;
        $this->emissionService = $emissionService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Liste des documents
     */
    public function index(Request $request)
    {
        $query = Document::with(['etudiant', 'administration']);

        // Filtre par utilisateur si c'est un étudiant
        if ($request->user()->role === 'etudiant') {
            $query->where('etudiant_id', $request->user()->id);
        }

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par type
        if ($request->has('type_document')) {
            $query->where('type_document', $request->type_document);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('uuid_document', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderBy('date_certification', 'desc')
                          ->paginate($request->get('per_page', 15));

        return DocumentResource::collection($documents);
    }

    /**
     * Récupérer les documents (diplômes) de l'étudiant connecté
     * Recherche par etudiant_id OU par numero_etudiant dans les metadata
     */
    public function myDocuments(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'etudiant') {
            return response()->json([
                'message' => 'Cette route est réservée aux étudiants',
            ], 403);
        }

        $query = Document::with(['administration'])
            ->where(function ($q) use ($user) {
                // Chercher par ID utilisateur
                $q->where('etudiant_id', $user->id);
                
                // OU par numéro étudiant dans les metadata
                if ($user->numero_etudiant) {
                    $q->orWhereJsonContains('metadata->student_id', $user->numero_etudiant);
                }
            })
            ->where('statut', 'ACTIF');

        // Filtre par type de document
        if ($request->has('type_document')) {
            $query->where('type_document', $request->type_document);
        }

        $documents = $query->orderBy('date_emission', 'desc')
                          ->paginate($request->get('per_page', 15));

        return DocumentResource::collection($documents);
    }

    /**
     * Créer un document (flux d'émission complet)
     */
    public function store(StoreDocumentRequest $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer l'administration de l'utilisateur admin connecté
            if ($user->role === 'admin') {
                $administration = \App\Models\Administration::where('email_contact', $user->email)->first();
                
                // Si aucune administration trouvée, créer une administration par défaut pour admin@ucad.edu.sn
                if (!$administration) {
                    if ($user->email === 'admin@ucad.edu.sn') {
                        $administration = \App\Models\Administration::firstOrCreate(
                            [
                                'email_contact' => 'admin@ucad.edu.sn',
                            ],
                            [
                                'nom' => 'Université Cheikh Anta Diop (UCAD)',
                                'type_administration' => 'universite',
                                'pays' => 'Sénégal',
                                'ville' => 'Dakar',
                                'adresse' => 'Avenue Cheikh Anta Diop, BP 5005, Dakar-Fann',
                                'statut' => 'verifie',
                                'cle_publique_ed25519' => 'temp_will_be_replaced_by_observer', // Sera remplacé par l'observer
                            ]
                        );
                    } else {
                        return response()->json([
                            'message' => 'Aucune administration trouvée pour cet utilisateur. Veuillez contacter le support.',
                        ], 404);
                    }
                }
            } else {
                return response()->json([
                    'message' => 'Seuls les administrateurs peuvent certifier des documents.',
                ], 403);
            }
            
            $validated = $request->validated();
            $validated['administration_id'] = $administration->id;
            $validated['operateur_id'] = $user->id;

            // Par défaut, associer le document à l'opérateur (admin) si aucun étudiant n'est fourni
            if (empty($validated['etudiant_id'])) {
                $validated['etudiant_id'] = $user->id;
            }
            
            // Gérer generate_pdf manuellement (peut être une chaîne "true" depuis FormData)
            $generatePdf = false;
            if ($request->has('generate_pdf')) {
                $generatePdfValue = $request->input('generate_pdf');
                $generatePdf = filter_var($generatePdfValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($generatePdf === null) {
                    $generatePdf = in_array(strtolower((string)$generatePdfValue), ['true', '1', 'yes', 'on']);
                }
            }
            
            // Si un fichier est uploadé (diplôme existant)
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('documents', 'public');
                $validated['file_url'] = $path;
                $validated['generate_pdf'] = false;
            } 
            // Sinon, si c'est une génération de diplôme, on devra générer le PDF
            else if ($generatePdf) {
                // Le service d'émission devra générer le PDF à partir des métadonnées
                $validated['generate_pdf'] = true; // Flag pour indiquer qu'on doit générer le PDF
            }
            
            // Gérer les métadonnées (peuvent être une string JSON depuis FormData)
            if (isset($validated['metadata']) && is_string($validated['metadata'])) {
                $validated['metadata'] = json_decode($validated['metadata'], true);
            }

            // Utiliser le service d'émission complet
            $document = $this->emissionService->emitDocument($validated);

            // Retourner la réponse immédiatement pour ne pas bloquer
            $response = response()->json([
                'message' => 'Document certifié avec succès',
                'document' => new DocumentResource($document->load(['etudiant', 'administration'])),
            ], 201);

            // Lier les compétences en arrière-plan (après la réponse HTTP)
            if (isset($validated['metadata']['diplome_id']) && $validated['metadata']['diplome_id']) {
                dispatch(function () use ($validated, $document) {
                    try {
                        $diplome = \App\Models\Diplome::with('competences')->find($validated['metadata']['diplome_id']);
                        
                        if ($diplome && $diplome->competences && $diplome->competences->count() > 0) {
                            // Trouver l'étudiant
                            $etudiant = null;
                            
                            // Si etudiant_id est fourni directement
                            if (!empty($validated['etudiant_id'])) {
                                $etudiant = \App\Models\User::find($validated['etudiant_id']);
                            }
                            
                            // Sinon, chercher par student_id dans les métadonnées
                            if (!$etudiant && isset($validated['metadata']['student_id'])) {
                                $etudiant = \App\Models\User::where('numero_etudiant', $validated['metadata']['student_id'])->first();
                            }
                            
                            // Si on a trouvé l'étudiant, lier les compétences
                            if ($etudiant) {
                                $profilEtudiant = \App\Models\ProfilEtudiant::where('utilisateur_id', $etudiant->id)->first();
                                
                                // Créer le profil s'il n'existe pas
                                if (!$profilEtudiant) {
                                    $profilEtudiant = \App\Models\ProfilEtudiant::create([
                                        'utilisateur_id' => $etudiant->id,
                                    ]);
                                }
                                
                                if ($profilEtudiant) {
                                    foreach ($diplome->competences as $competence) {
                                        // Vérifier si la compétence n'est pas déjà liée
                                        $existing = \App\Models\ProfilCompetence::where('profil_etudiant_id', $profilEtudiant->id)
                                            ->where('competence_id', $competence->id)
                                            ->first();
                                        
                                        if (!$existing) {
                                            // Utiliser un niveau valide selon la contrainte de la base de données
                                            \App\Models\ProfilCompetence::create([
                                                'profil_etudiant_id' => $profilEtudiant->id,
                                                'competence_id' => $competence->id,
                                                'niveau' => 'expert', // Les diplômes certifient un niveau expert
                                                'source' => 'validation_admin', // Source valide : validation par l'admin universitaire
                                                'source_document_id' => $document->id,
                                                'score_confiance' => 1.0,
                                                'validee_par_etudiant' => true, // Auto-validé car certifié par l'université
                                                'date_extraction' => \Carbon\Carbon::now(),
                                            ]);
                                        }
                                    }

                                    // Régénérer l'embedding du candidat
                                    app(\App\Services\EmbeddingService::class)->generateCandidateEmbedding($etudiant->id);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Logger l'erreur mais ne pas bloquer
                        \Log::warning('Erreur lors de la liaison des compétences du diplôme (async)', [
                            'diplome_id' => $validated['metadata']['diplome_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                })->afterResponse();
            }

            return $response;
        } catch (\Exception $e) {
            // Nettoyer le message d'erreur pour éviter les problèmes d'UTF-8 dans la réponse JSON
            $rawMessage = $e->getMessage();
            $encoding = mb_detect_encoding($rawMessage, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';
            $cleanMessage = mb_convert_encoding($rawMessage, 'UTF-8', $encoding);
            $cleanMessage = iconv('UTF-8', 'UTF-8//IGNORE', $cleanMessage);

            \Log::error('Erreur lors de la certification', [
                'user_id' => $request->user()->id,
                'error' => $cleanMessage,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la certification du document',
                'error' => $cleanMessage,
                'details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Afficher un document
     */
    public function show(Request $request, $id)
    {
        $document = Document::with(['etudiant', 'administration', 'verificationLogs', 'revocations'])
                           ->findOrFail($id);

        // Vérification des permissions
        if ($request->user()->role === 'etudiant' && $document->etudiant_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return new DocumentResource($document);
    }

    /**
     * Mettre à jour un document
     */
    public function update(UpdateDocumentRequest $request, $id)
    {
        $document = Document::findOrFail($id);

        // Seul le propriétaire peut mettre à jour
        if ($document->etudiant_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();
        $document->update($validated);

        return response()->json([
            'message' => 'Document mis à jour avec succès',
            'document' => new DocumentResource($document->fresh()),
        ]);
    }

    /**
     * Supprimer un document
     */
    public function destroy(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Seul le propriétaire ou un admin peut supprimer
        if ($document->etudiant_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document supprimé avec succès',
        ]);
    }

    /**
     * Télécharger un document
     */
    public function download(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $user = $request->user();

        // Vérification des permissions pour les étudiants
        if ($user->role === 'etudiant') {
            $hasAccess = false;
            
            // Vérifier par etudiant_id
            if ($document->etudiant_id === $user->id) {
                $hasAccess = true;
            }
            
            // Vérifier par numero_etudiant dans les metadata
            if (!$hasAccess && $user->numero_etudiant) {
                $metadata = $document->metadata;
                if (is_array($metadata) && isset($metadata['student_id']) && $metadata['student_id'] === $user->numero_etudiant) {
                    $hasAccess = true;
                }
            }
            
            if (!$hasAccess) {
                return response()->json(['message' => 'Accès refusé'], 403);
            }
        }

        // Vérifier que le fichier existe
        if (empty($document->file_url)) {
            return response()->json(['message' => 'Aucun fichier associé à ce document'], 404);
        }

        // Le fichier est stocké sur le disque 'public'
        if (!Storage::disk('public')->exists($document->file_url)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        $filePath = Storage::disk('public')->path($document->file_url);
        $fileName = basename($document->file_url);
        
        // Déterminer le type MIME selon l'extension
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = match($extension) {
            'html' => 'text/html',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
        
        // Log de téléchargement
        \Log::info('Téléchargement de document', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'file_path' => $document->file_url,
        ]);
        
        return response()->download($filePath, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * Vérifier un document (publique)
     * Peut recevoir un UUID, un hash SHA256, ou un fichier uploadé
     */
    public function verify(Request $request)
    {
        // Si un fichier est uploadé, vérifier par fichier
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $result = $this->verificationService->verifyByFile(
                $uploadedFile,
                $request->ip(),
                $request->userAgent()
            );
            return response()->json($result);
        }

        // Sinon, vérifier par UUID ou hash
        $validated = $request->validate([
            'uuid' => 'required_without:hash_sha256|string|size:36',
            'hash_sha256' => 'required_without:uuid|string|size:64',
        ]);
        
        $result = $this->verificationService->verifyDocument(
            $validated['uuid'] ?? $validated['hash_sha256'],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($result);
    }

    /**
     * Vérifier un document par UUID (publique)
     */
    public function verifyByUuid(Request $request, $uuid)
    {
        $result = $this->verificationService->verifyByUuid(
            $uuid,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($result);
    }

    /**
     * Obtenir le QR code d'un document (publique)
     */
    public function getQrCode(Request $request, $uuid)
    {
        $document = Document::where('uuid_document', $uuid)->firstOrFail();

        // Générer le QR code s'il n'existe pas
        $qrCodeUrl = $this->qrCodeService->getOrGenerateQrCode($document);
        $verificationUrl = $this->qrCodeService->getVerificationUrl($document);

        return response()->json([
            'qr_code_url' => $qrCodeUrl,
            'verification_url' => $verificationUrl,
            'uuid' => $document->uuid_document,
            'document' => new DocumentResource($document),
        ]);
    }

    /**
     * Obtenir les logs de vérification d'un document
     */
    public function getVerificationLogs(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Vérification des permissions
        if ($request->user()->role === 'etudiant' && $document->etudiant_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $logs = VerificationLog::where('document_id', $id)
                              ->orderBy('date_verification', 'desc')
                              ->paginate($request->get('per_page', 15));

        return response()->json($logs);
    }

    /**
     * Révoquer un document
     */
    public function revoke(RevokeDocumentRequest $request, $id)
    {
        $document = Document::findOrFail($id);

        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Créer la révocation
            $revocation = Revocation::create([
                'document_id' => $document->id,
                'administration_id' => $validated['administration_id'],
                'operateur_id' => $request->user()->id,
                'motif_categorie' => $validated['motif_categorie'],
                'motif_detail' => $validated['motif_detail'],
                'document_justificatif_url' => $validated['document_justificatif_url'] ?? null,
                'irreversible' => $validated['irreversible'] ?? true,
            ]);

            // Mettre à jour le statut du document
            $document->update(['statut' => 'REVOQUE']);

            DB::commit();

            return response()->json([
                'message' => 'Document révoqué avec succès',
                'revocation' => $revocation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la révocation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

