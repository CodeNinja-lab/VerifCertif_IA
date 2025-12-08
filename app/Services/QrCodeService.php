<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Générer un QR code pour un document
     */
    public function generateQrCode(Document $document, $size = 300): string
    {
        // URL de vérification publique
        $verificationUrl = url("/api/v1/documents/{$document->uuid_document}/verify");
        
        // Générer le QR code en SVG (plus léger que PNG)
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size($size)
            ->format('svg')
            ->errorCorrection('H') // Niveau de correction d'erreur élevé
            ->generate($verificationUrl);
        
        $qrCodeSvg = $qrCode;
        
        // Sauvegarder le QR code
        $qrCodePath = "qrcodes/{$document->uuid_document}.svg";
        Storage::disk('public')->put($qrCodePath, $qrCodeSvg);
        
        // Retourner l'URL publique
        $qrCodeUrl = Storage::disk('public')->url($qrCodePath);
        
        // Mettre à jour le document avec l'URL du QR code
        $document->update(['qr_code_url' => $qrCodeUrl]);
        
        return $qrCodeUrl;
    }

    /**
     * Générer un QR code en PNG pour incrustation sur document PDF
     */
    public function generateQrCodePng(Document $document, $size = 200): string
    {
        // URL de vérification publique
        $verificationUrl = url("/api/v1/documents/{$document->uuid_document}/verify");
        
        // Générer le QR code en PNG
        $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::size($size)
            ->format('png')
            ->errorCorrection('H')
            ->margin(2)
            ->generate($verificationUrl);
        
        // Sauvegarder le QR code
        $qrCodePath = "qrcodes/{$document->uuid_document}.png";
        Storage::disk('public')->put($qrCodePath, $qrCodePng);
        
        // Retourner l'URL publique
        return Storage::disk('public')->url($qrCodePath);
    }

    /**
     * Obtenir l'URL de vérification d'un document
     */
    public function getVerificationUrl(Document $document): string
    {
        return url("/api/v1/documents/{$document->uuid_document}/verify");
    }

    /**
     * Générer ou récupérer le QR code d'un document
     */
    public function getOrGenerateQrCode(Document $document): string
    {
        if ($document->qr_code_url && Storage::disk('public')->exists($this->getQrCodePath($document))) {
            return $document->qr_code_url;
        }
        
        return $this->generateQrCode($document);
    }

    /**
     * Obtenir le chemin du fichier QR code
     */
    protected function getQrCodePath(Document $document): string
    {
        return "qrcodes/{$document->uuid_document}.svg";
    }
}

