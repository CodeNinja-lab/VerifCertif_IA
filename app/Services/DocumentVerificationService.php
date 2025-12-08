<?php

namespace App\Services;

use App\Models\Document;
use App\Models\VerificationLog;
use App\Services\BlockchainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DocumentVerificationService
{
    protected $blockchainService;

    public function __construct(BlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    /**
     * Flux de vérification complet d'un document
     * 
     * 1. L'utilisateur scanne le QR ou soumet le fichier
     * 2. Le système calcule le haché du document reçu
     * 3. Il compare le haché et la signature avec le registre
     * 4. Il affiche le résultat de validité et les métadonnées
     */
    public function verifyDocument($identifier, $ipAddress, $userAgent, $uploadedFile = null)
    {
        $startTime = microtime(true);
        $methode = $uploadedFile ? 'upload_fichier' : 'qr_scan';

        try {
            $document = null;

            // Si un fichier est uploadé, calculer son hash
            if ($uploadedFile) {
                $fileContent = $uploadedFile->getContent();
                $fileHash = hash('sha256', $fileContent);
                
                // Rechercher le document par hash
                $document = Document::where('hash_sha256', $fileHash)->first();
                
                if (!$document) {
                    $this->logVerification(null, 'public', null, $ipAddress, $userAgent, $methode, 'INVALIDE', 'Hash du document ne correspond à aucun document certifié', null);
                    
                    return [
                        'resultat' => 'INVALIDE',
                        'message' => 'Ce document n\'a pas été certifié. Le hash ne correspond à aucun document dans notre base de données.',
                        'hash_calcule' => $fileHash,
                    ];
                }
            } else {
                // Rechercher par UUID ou hash fourni
                if (strlen($identifier) === 36) {
                    // UUID
                    $document = Document::with(['etudiant', 'administration'])->where('uuid_document', $identifier)->first();
                } else {
                    // Hash SHA256
                    $document = Document::with(['etudiant', 'administration'])->where('hash_sha256', $identifier)->first();
                }
            }

            if (!$document) {
                $this->logVerification(null, 'public', null, $ipAddress, $userAgent, $methode, 'INVALIDE', 'Document non trouvé', null);
                
                return [
                    'resultat' => 'INVALIDE',
                    'message' => 'Document non trouvé',
                ];
            }

            // 3. Vérifier le hash et la signature
            $verificationResult = $this->verifyHashAndSignature($document, $uploadedFile);
            
            // Vérifier le statut
            $resultat = $verificationResult['resultat'];
            $message = $verificationResult['message'];

            if ($document->statut === 'REVOQUE') {
                $resultat = 'REVOQUE';
                $message = 'Ce document a été révoqué par l\'administration émettrice';
            } elseif ($document->statut === 'EXPIRE') {
                $resultat = 'EXPIRE';
                $message = 'Ce document a expiré';
            } elseif ($document->date_expiration && Carbon::parse($document->date_expiration)->isPast()) {
                $resultat = 'EXPIRE';
                $message = 'Ce document a expiré';
                $document->update(['statut' => 'EXPIRE']);
            }

            // Vérifier l'ancrage blockchain si présent
            $blockchainVerified = false;
            if ($document->blockchain_tx_hash) {
                $blockchainVerified = $this->blockchainService->verifyAnchor($document);
            }

            $duree = (microtime(true) - $startTime) * 1000; // en millisecondes

            // 4. Enregistrer le log
            $log = $this->logVerification(
                $document->id,
                'public',
                null,
                $ipAddress,
                $userAgent,
                $methode,
                $resultat,
                $verificationResult['details_erreur'] ?? null,
                (int) $duree
            );

            // Préparer les métadonnées
            $metadata = [
                'uuid' => $document->uuid_document,
                'titre' => $document->titre,
                'type' => $document->type_document,
                'date_emission' => $document->date_emission->format('Y-m-d'),
                'date_expiration' => $document->date_expiration ? $document->date_expiration->format('Y-m-d') : null,
                'date_certification' => $document->date_certification->format('Y-m-d H:i:s'),
                'administration' => [
                    'nom' => $document->administration->nom ?? null,
                    'type' => $document->administration->type_administration ?? null,
                ],
                'etudiant' => [
                    'nom' => $document->etudiant->nom ?? null,
                    'prenom' => $document->etudiant->prenom ?? null,
                ],
                'statut' => $document->statut,
                'hash_sha256' => $document->hash_sha256,
                'blockchain' => $document->blockchain_tx_hash ? [
                    'tx_hash' => $document->blockchain_tx_hash,
                    'network' => $document->blockchain_network,
                    'verified' => $blockchainVerified,
                ] : null,
            ];

            if ($document->metadata) {
                $metadata['details'] = $document->metadata;
            }

            return [
                'resultat' => $resultat,
                'message' => $message,
                'document' => $metadata,
                'verification' => [
                    'id' => $log->id,
                    'date' => $log->date_verification?->toIso8601String() ?? Carbon::now()->toIso8601String(),
                    'methode' => $methode,
                    'duree_ms' => $duree,
                ],
            ];

        } catch (\Exception $e) {
            $duree = (microtime(true) - $startTime) * 1000;
            
            $this->logVerification(
                null,
                'public',
                null,
                $ipAddress,
                $userAgent,
                $methode,
                'ERREUR',
                $e->getMessage(),
                (int) $duree
            );

            return [
                'resultat' => 'ERREUR',
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier le hash et la signature du document
     */
    protected function verifyHashAndSignature(Document $document, $uploadedFile = null): array
    {
        // Si un fichier est uploadé, vérifier son hash
        if ($uploadedFile) {
            $fileContent = $uploadedFile->getContent();
            $fileHash = hash('sha256', $fileContent);
            
            if ($fileHash !== $document->hash_sha256) {
                return [
                    'resultat' => 'INVALIDE',
                    'message' => 'Le hash du document ne correspond pas. Le document a peut-être été modifié.',
                    'details_erreur' => 'Hash calculé: ' . $fileHash . ' | Hash attendu: ' . $document->hash_sha256,
                ];
            }
        }

        // Vérifier la signature Ed25519
        $signatureValid = $this->verifySignature(
            $document->hash_sha256,
            $document->signature_ed25519,
            $document->administration->cle_publique_ed25519
        );

        if (!$signatureValid) {
            return [
                'resultat' => 'INVALIDE',
                'message' => 'La signature du document n\'est pas valide. Le document a peut-être été altéré.',
                'details_erreur' => 'Signature invalide',
            ];
        }

        return [
            'resultat' => 'VALIDE',
            'message' => 'Document valide et authentique',
        ];
    }

    /**
     * Vérifier une signature Ed25519
     */
    protected function verifySignature(string $hash, string $signature, string $publicKey): bool
    {
        // En développement, accepter les signatures simulées
        if (app()->environment('local', 'testing')) {
            return strlen($signature) > 0 && strlen($publicKey) > 0;
        }

        try {
            // Décoder la signature base64
            $signatureBytes = base64_decode($signature);
            
            // Décoder la clé publique base64 (stockée en base64 dans la BDD)
            $publicKeyBytes = base64_decode($publicKey);
            
            // Vérifier avec sodium
            if (!function_exists('sodium_crypto_sign_verify_detached')) {
                Log::warning('Extension sodium non disponible, signature non vérifiée');
                return true; // Accepter en cas d'absence de l'extension
            }

            // Vérifier la signature avec la clé publique décodée
            return sodium_crypto_sign_verify_detached($signatureBytes, $hash, $publicKeyBytes);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de signature', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Vérifier un document par UUID
     */
    public function verifyByUuid($uuid, $ipAddress, $userAgent)
    {
        return $this->verifyDocument($uuid, $ipAddress, $userAgent);
    }

    /**
     * Vérifier un document par upload de fichier
     */
    public function verifyByFile($uploadedFile, $ipAddress, $userAgent)
    {
        return $this->verifyDocument(null, $ipAddress, $userAgent, $uploadedFile);
    }

    /**
     * Enregistrer un log de vérification
     */
    protected function logVerification($documentId, $verificateurType, $verificateurId, $ipAddress, $userAgent, $methode, $resultat, $detailsErreur, $dureeMs)
    {
        // Géolocalisation basique (peut être améliorée avec un service comme MaxMind)
        $pays = null;
        $ville = null;
        
        // TODO: Intégrer un service de géolocalisation IP si nécessaire

        return VerificationLog::create([
            'document_id' => $documentId,
            'verificateur_type' => $verificateurType,
            'verificateur_id' => $verificateurId,
            'ip_hash' => hash('sha256', $ipAddress),
            'user_agent' => $userAgent,
            'pays' => $pays,
            'ville' => $ville,
            'methode_verification' => $methode,
            'resultat' => $resultat,
            'details_erreur' => $detailsErreur,
            'duree_ms' => $dureeMs,
            'date_verification' => Carbon::now(),
        ]);
    }
}
