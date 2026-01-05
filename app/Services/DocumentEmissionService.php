<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Administration;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class DocumentEmissionService
{
    protected $qrCodeService;
    protected $blockchainService;
    protected $keyService;

    public function __construct(
        QrCodeService $qrCodeService,
        BlockchainService $blockchainService,
        SimpleKeyManagementService $keyService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->blockchainService = $blockchainService;
        $this->keyService = $keyService;
    }

    /**
     * Nettoie une chaîne pour s'assurer qu'elle est en UTF-8 valide
     */
    protected function sanitizeUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Détecter l'encodage d'entrée
        $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';

        // Convertir en UTF-8
        $clean = mb_convert_encoding($value, 'UTF-8', $encoding);

        // Supprimer les octets invalides
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $clean);

        return $clean;
    }

    /**
     * Flux d'émission complet d'un document
     * 
     * 1. L'université téléverse un document
     * 2. Le système calcule le haché SHA-256
     * 3. Le serveur signe le haché avec la clé privée
     * 4. Le certificat est créé et enregistré avec métadonnées
     * 5. Le QR code est généré et apposé sur le document
     * 6. Ancrage sur blockchain
     */
    public function emitDocument(array $data): Document
    {
        DB::beginTransaction();
        
        try {
            // Générer l'UUID du document en avance pour pouvoir créer le QR code
            $uuidDocument = \Illuminate\Support\Str::uuid();
            
            // 1. Si on doit générer le PDF, le créer d'abord avec le QR code
            if (isset($data['generate_pdf']) && $data['generate_pdf']) {
                $filePath = $this->generateDiplomaPdf($data, $uuidDocument);
            } else {
                // Sinon, le fichier est déjà téléversé, on récupère son chemin
                $filePath = $data['file_url'];
            }
            
            // 2. Calculer le hash SHA-256 du fichier
            // Le fichier est stocké sur le disque 'public'
            $fileContent = Storage::disk('public')->get($filePath);
            if (!$fileContent) {
                throw new \Exception("Impossible de lire le fichier : {$filePath}");
            }
            $hashSha256 = hash('sha256', $fileContent);
            
            // Vérifier si ce hash existe déjà (éviter les doublons)
            $existingDocument = Document::where('hash_sha256', $hashSha256)->first();
            if ($existingDocument) {
                throw new \Exception('Ce document a déjà été certifié (même hash SHA-256)');
            }
            
            // 3. Signer le hash avec la clé privée Ed25519 de l'administration
            $administration = Administration::findOrFail($data['administration_id']);
            $signatureEd25519 = $this->signHash($hashSha256, $administration);
            
            // 4. Créer le document avec métadonnées
            // Gérer les métadonnées (peuvent être un array ou une string JSON)
            $metadata = $data['metadata'] ?? null;
            if (is_string($metadata)) {
                $metadata = json_decode($this->sanitizeUtf8($metadata), true);
            }
            
            // Récupérer l'ID étudiant depuis les métadonnées ou l'ID fourni
            $etudiantId = $data['etudiant_id'] ?? null;
            
            // Si pas d'etudiant_id mais student_id dans metadata, chercher l'étudiant par numero_etudiant
            if (!$etudiantId && isset($metadata['student_id'])) {
                $etudiant = \App\Models\User::where('numero_etudiant', $metadata['student_id'])
                    ->where('role', 'etudiant')
                    ->first();
                if ($etudiant) {
                    $etudiantId = $etudiant->id;
                }
            }
            
            // Pour l'instant, si pas d'étudiant, on utilise l'admin
            // Solution temporaire : utiliser l'ID de l'opérateur comme étudiant si pas d'étudiant spécifié
            if (!$etudiantId) {
                $etudiantId = $data['operateur_id'] ?? null;
            }
            
            if (!$etudiantId) {
                throw new \Exception('Un ID étudiant est requis pour créer le document');
            }
            
            $document = Document::create([
                'uuid_document' => $uuidDocument,
                'etudiant_id' => $etudiantId,
                'administration_id' => $data['administration_id'],
                'type_document' => $data['type_document'],
                'titre' => $data['titre'],
                'file_url' => $filePath,
                'file_size_kb' => isset($data['file_size_kb']) ? $data['file_size_kb'] : (int)(Storage::disk('public')->size($filePath) / 1024),
                'hash_sha256' => $hashSha256,
                'signature_ed25519' => $signatureEd25519,
                'statut' => 'ACTIF',
                'date_emission' => $data['date_emission'] ?? Carbon::now(),
                'date_expiration' => $data['date_expiration'] ?? null,
                'metadata' => $metadata,
            ]);
            
            // 5. Générer le QR code
            $qrCodeUrl = $this->qrCodeService->generateQrCode($document);
            
            // 6. Ancrer sur blockchain (optionnel, peut échouer sans bloquer)
            try {
                $txHash = $this->blockchainService->anchorDocument($document);
                if ($txHash) {
                    $document->refresh(); // Recharger pour avoir le tx_hash
                }
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas l'émission
                Log::warning("Échec de l'ancrage blockchain", [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Ne pas bloquer l'émission du document
            }
            
            // Log d'audit
            AuditLog::create([
                'utilisateur_id' => $data['operateur_id'] ?? null,
                'ip_hash' => hash('sha256', request()->ip()),
                'action' => 'document_emission',
                'objet_type' => 'document',
                'objet_id' => $document->id,
                'statut' => 'succes',
                'details' => [
                    'uuid' => $document->uuid_document,
                    'type' => $document->type_document,
                    'administration_id' => $administration->id,
                ],
                'user_agent' => request()->userAgent(),
            ]);
            
            // Envoyer une notification à l'étudiant
            try {
                $notificationService = new NotificationService();
                $notificationService->certificatDisponible(
                    $etudiantId,
                    $document->titre,
                    $document->id
                );
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas le processus
                Log::warning("Échec envoi notification certificat", [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Commit de la transaction
            DB::commit();
            
            // Recharger le document avec ses relations
            $refreshedDocument = $document->fresh(['etudiant', 'administration']);
            
            // Vérifier que le document existe toujours (au cas où il aurait été supprimé)
            if (!$refreshedDocument) {
                throw new \Exception("Le document a été créé mais n'a pas pu être rechargé depuis la base de données");
            }
            
            return $refreshedDocument;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log d'erreur (en s'assurant que le message est en UTF-8 valide)
            AuditLog::create([
                'utilisateur_id' => $data['operateur_id'] ?? null,
                'ip_hash' => hash('sha256', request()->ip()),
                'action' => 'document_emission',
                'objet_type' => 'document',
                'objet_id' => null,
                'statut' => 'erreur',
                'message_erreur' => $this->sanitizeUtf8($e->getMessage()),
                'user_agent' => $this->sanitizeUtf8(request()->userAgent()),
            ]);
            
            throw $e;
        }
    }

    /**
     * Signer un hash avec Ed25519
     * 
     * Utilise la clé privée stockée de manière sécurisée dans /storage/app/keys/
     */
    protected function signHash(string $hash, Administration $administration): string
    {
        // Vérifier que l'extension sodium est disponible
        $sodiumAvailable = function_exists('sodium_crypto_sign_detached');
        
        // Essayer de récupérer la clé privée
        $privateKey = null;
        if ($sodiumAvailable) {
            try {
                $privateKey = $this->keyService->getPrivateKey($administration);
            } catch (\Exception $e) {
                Log::warning("Impossible de récupérer la clé privée - Utilisation d'une signature factice", [
                    'administration_id' => $administration->id,
                    'error' => $e->getMessage(),
                ]);
                $privateKey = null;
            }
        }
        
        // Si sodium n'est pas disponible OU si la clé privée n'existe pas
        // Utiliser une signature factice pour le développement
        if (!$sodiumAvailable || !$privateKey) {
            Log::warning("Utilisation d'une signature factice pour le développement", [
                'administration_id' => $administration->id,
                'sodium_available' => $sodiumAvailable,
                'private_key_available' => $privateKey !== null,
            ]);
            
            // Générer une signature factice basée sur le hash et l'ID de l'administration
            // Cette signature ne peut pas être vérifiée mais permet de continuer le développement
            $fakeSignature = hash('sha256', $hash . $administration->id . config('app.key'));
            return base64_encode($fakeSignature);
        }

        // Signer le hash avec la clé privée (cas normal en production)
        try {
            $signature = sodium_crypto_sign_detached($hash, $privateKey);
            return base64_encode($signature);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la signature", [
                'administration_id' => $administration->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Erreur lors de la signature du document : " . $e->getMessage());
        }
    }

    /**
     * Générer un PDF de diplôme à partir des métadonnées
     */
    protected function generateDiplomaPdf(array $data, string $uuidDocument): string
    {
        $metadata = is_array($data['metadata']) ? $data['metadata'] : json_decode($data['metadata'] ?? '{}', true);
        
        // Générer le QR code pour ce document en SVG (ne nécessite pas imagick)
        try {
            $verificationUrl = url("/api/v1/documents/{$uuidDocument}/verify");
            
            // Générer systématiquement le QR code en SVG (pas de dépendance à imagick)
            $qrCodeData = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
                ->format('svg')
                ->errorCorrection('H')
                ->generate($verificationUrl);
            
            $qrCodeFormat = 'svg';
            $mimeType = 'image/svg+xml';
            
            if (empty($qrCodeData)) {
                throw new \Exception("Le QR code généré est vide");
            }
            
            // Encoder le QR code en base64 pour l'inclure directement dans le HTML
            $qrCodeBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($qrCodeData);
            
            // Sauvegarder aussi le QR code pour référence future
            $qrCodePath = "qrcodes/{$uuidDocument}.{$qrCodeFormat}";
            Storage::disk('public')->put($qrCodePath, $qrCodeData);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du QR code", [
                'error' => $e->getMessage(),
                'uuid' => $uuidDocument,
            ]);
            throw new \Exception("Erreur lors de la génération du QR code: " . $e->getMessage());
        }
        
        // Créer le contenu HTML du diplôme avec QR code
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diplôme</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: "Times New Roman", serif;
            padding: 40px 60px;
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .content {
            flex: 1;
        }
        .header {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 50px;
        }
        .degree-type {
            font-size: 20px;
            margin: 50px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .certification-text {
            font-size: 16px;
            margin: 30px 0;
            line-height: 1.8;
        }
        .student-name {
            font-size: 22px;
            font-weight: bold;
            margin: 40px 0;
            color: #1a1a1a;
        }
        .degree-title {
            font-size: 20px;
            margin: 30px 0;
            font-weight: 600;
        }
        .grade {
            font-size: 16px;
            margin: 25px 0;
        }
        .date {
            font-size: 14px;
            margin-top: 40px;
        }
        .notes {
            font-size: 12px;
            margin-top: 40px;
            text-align: left;
            padding: 20px;
            border-top: 1px solid #ccc;
        }
        .qr-section {
            margin-top: 60px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .qr-code {
            width: 150px;
            height: 150px;
            background: white;
            padding: 10px;
            border: 2px solid #333;
            border-radius: 8px;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .qr-text {
            font-size: 11px;
            color: #666;
            max-width: 300px;
            text-align: center;
        }
        .verification-url {
            font-size: 10px;
            color: #999;
            word-break: break-all;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="header">UNIVERSITÉ CHEIKH ANTA DIOP</div>
        <div class="subtitle">Dakar, Sénégal</div>
        
        <div class="degree-type">DIPLÔME DE ' . mb_strtoupper(mb_convert_encoding($metadata['degree_type'] ?? 'MASTER', 'UTF-8', 'auto'), 'UTF-8') . '</div>
        
        <p class="certification-text">L\'Université Cheikh Anta Diop certifie que</p>
        
        <div class="student-name">' . htmlspecialchars(mb_convert_encoding(($metadata['student_first_name'] ?? '') . ' ' . ($metadata['student_name'] ?? ''), 'UTF-8', 'auto'), ENT_QUOTES, 'UTF-8') . '</div>
        
        <p class="certification-text">a obtenu le diplôme de</p>
        
        <div class="degree-title">' . htmlspecialchars(mb_convert_encoding($data['titre'] ?? 'Diplôme', 'UTF-8', 'auto'), ENT_QUOTES, 'UTF-8') . '</div>';
        
        if (!empty($metadata['grade'])) {
            $grade = mb_convert_encoding($metadata['grade'], 'UTF-8', 'auto');
            $html .= '<div class="grade">avec la mention : <strong>' . htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') . '</strong></div>';
        }
        
        if (!empty($data['date_emission'])) {
            $html .= '<div class="date">Délivré le ' . Carbon::parse($data['date_emission'])->format('d/m/Y') . '</div>';
        }
        
        if (!empty($metadata['notes'])) {
            $notes = mb_convert_encoding($metadata['notes'], 'UTF-8', 'auto');
            $html .= '<div class="notes"><strong>Notes :</strong> ' . htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        // Ajouter le QR code en bas
        $html .= '
    </div>
    
    <div class="qr-section">
        <div class="qr-code">
            <img src="' . $qrCodeBase64 . '" alt="QR Code" style="width: 150px; height: 150px; display: block; margin: 0 auto;" />
        </div>
        <div class="qr-text">
            <strong>Code de vérification</strong><br>
            Scannez ce code pour vérifier l\'authenticité de ce diplôme
        </div>
        <div class="verification-url">' . htmlspecialchars($verificationUrl) . '</div>
    </div>
</body>
</html>';
        
        // S'assurer que le HTML est bien en UTF-8 (évite l'erreur "Malformed UTF-8 characters")
        // Nettoyer et forcer l'encodage UTF-8 de manière plus agressive
        
        // Détecter et convertir l'encodage si nécessaire
        $detectedEncoding = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', $detectedEncoding);
        }
        
        // Supprimer les caractères invalides UTF-8 avec iconv
        $html = iconv('UTF-8', 'UTF-8//IGNORE', $html);
        
        // Vérifier une dernière fois l'encodage
        if (!mb_check_encoding($html, 'UTF-8')) {
            // Dernière tentative : forcer UTF-8
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }
        
        // Convertir le HTML en PDF avec Snappy (wkhtmltopdf) - plus simple et fiable sur Windows
        try {
            // Vérifier si Snappy est disponible
            if (!class_exists('\Barryvdh\Snappy\Facades\SnappyPdf')) {
                throw new \Exception("Snappy n'est pas installé. Veuillez installer Snappy avec: composer require barryvdh/laravel-snappy");
            }
            
            // Stocker le PDF dans storage/app/public/documents
            $fileName = 'diploma_' . $uuidDocument . '.pdf';
            $filePath = 'documents/' . $fileName;
            
            // S'assurer que le dossier existe
            $directory = storage_path('app/public/documents');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $fullPath = storage_path('app/public/' . $filePath);
            
            // Sauvegarder temporairement le HTML dans un fichier pour éviter les problèmes d'encodage
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempHtmlFile = $tempDir . '/diploma_' . $uuidDocument . '.html';
            
            // Écrire le HTML dans le fichier avec encodage UTF-8 explicite
            file_put_contents($tempHtmlFile, $html, LOCK_EX);
            
            try {
                // Générer le PDF avec Snappy en chargeant depuis le fichier (plus fiable pour l'encodage)
                // Utiliser loadFile si disponible, sinon utiliser loadHTML avec le contenu du fichier
                if (method_exists(PDF::class, 'loadFile')) {
                    PDF::loadFile($tempHtmlFile)
                        ->setOption('page-size', 'A4')
                        ->setOption('margin-top', '20mm')
                        ->setOption('margin-right', '20mm')
                        ->setOption('margin-bottom', '20mm')
                        ->setOption('margin-left', '20mm')
                        ->setOption('encoding', 'UTF-8')
                        ->setOption('enable-local-file-access', true)
                        ->save($fullPath);
                } else {
                    // Fallback: utiliser loadHTML mais avec le contenu du fichier
                    $htmlContent = file_get_contents($tempHtmlFile);
                    PDF::loadHTML($htmlContent)
                        ->setOption('page-size', 'A4')
                        ->setOption('margin-top', '20mm')
                        ->setOption('margin-right', '20mm')
                        ->setOption('margin-bottom', '20mm')
                        ->setOption('margin-left', '20mm')
                        ->setOption('encoding', 'UTF-8')
                        ->setOption('enable-local-file-access', true)
                        ->save($fullPath);
                }
            } finally {
                // Supprimer le fichier temporaire
                if (file_exists($tempHtmlFile)) {
                    @unlink($tempHtmlFile);
                }
            }
            
            Log::info("Diplôme PDF généré avec QR code et stocké", [
                'file_path' => $filePath,
                'file_size' => Storage::disk('public')->size($filePath),
                'uuid' => $uuidDocument,
            ]);
            
            return $filePath;
        } catch (\Exception $e) {
            // En cas d'erreur, lancer l'erreur avec un message clair
            Log::error("Erreur lors de la génération du PDF avec Snappy", [
                'error' => $e->getMessage(),
                'uuid' => $uuidDocument,
                'trace' => $e->getTraceAsString(),
            ]);
            
            $errorMsg = "Impossible de générer le PDF: " . $e->getMessage();
            if (strpos($e->getMessage(), 'wkhtmltopdf') !== false) {
                $errorMsg .= " Assurez-vous que wkhtmltopdf est installé. Téléchargez-le depuis: https://wkhtmltopdf.org/downloads.html";
            }
            
            throw new \Exception($errorMsg);
        }
    }
}

