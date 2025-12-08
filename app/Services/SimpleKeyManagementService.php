<?php

namespace App\Services;

use App\Models\Administration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des clés privées Ed25519
 * Stockage simple basé sur des fichiers dans /storage/app/keys/
 */
class SimpleKeyManagementService
{
    /**
     * Génère une paire de clés Ed25519 pour une administration
     * Stocke la clé privée dans un fichier sécurisé
     * Met à jour le chemin dans la base de données
     * 
     * @param Administration $administration
     * @return array ['public_key' => string, 'private_key_path' => string]
     * @throws \Exception
     */
    public function generateKeyPair(Administration $administration): array
    {
        // Vérifier que l'extension sodium est disponible
        if (!function_exists('sodium_crypto_sign_keypair')) {
            throw new \Exception('Extension sodium non disponible. Installez-la pour générer des clés Ed25519.');
        }

        // Générer la paire de clés Ed25519
        $keypair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keypair);
        $privateKey = sodium_crypto_sign_secretkey($keypair);

        // Encoder en base64 pour stockage
        $publicKeyBase64 = base64_encode($publicKey);
        $privateKeyBase64 = base64_encode($privateKey);

        // Définir le chemin de stockage (relatif au disk 'local' qui pointe vers storage/app/private)
        $keyDirectory = "keys/administrations/{$administration->id}";
        $privateKeyPath = "{$keyDirectory}/private.pem";

        // Créer le répertoire s'il n'existe pas
        if (!Storage::disk('local')->exists($keyDirectory)) {
            Storage::disk('local')->makeDirectory($keyDirectory);
        }

        // Sauvegarder la clé privée dans un fichier
        Storage::disk('local')->put($privateKeyPath, $privateKeyBase64);

        // Mettre à jour l'administration avec la clé publique et le chemin
        $administration->update([
            'cle_publique_ed25519' => $publicKeyBase64,
            'private_key_path' => $privateKeyPath,
        ]);

        // Sécuriser les permissions (si sur système Unix)
        $this->secureKeyFile($privateKeyPath);

        Log::info("Paire de clés Ed25519 générée", [
            'administration_id' => $administration->id,
            'administration_nom' => $administration->nom,
            'private_key_path' => $privateKeyPath,
        ]);

        return [
            'public_key' => $publicKeyBase64,
            'private_key_path' => $privateKeyPath,
        ];
    }

    /**
     * Récupère la clé privée depuis le stockage
     * 
     * @param Administration $administration
     * @return string Clé privée en format binaire (décodée)
     * @throws \Exception
     */
    public function getPrivateKey(Administration $administration): string
    {
        if (!$administration->private_key_path) {
            throw new \Exception("Aucune clé privée n'est configurée pour cette administration (ID: {$administration->id})");
        }

        if (!Storage::disk('local')->exists($administration->private_key_path)) {
            throw new \Exception("Le fichier de clé privée est introuvable : {$administration->private_key_path}");
        }

        // Lire la clé privée depuis le fichier
        $privateKeyBase64 = Storage::disk('local')->get($administration->private_key_path);

        // Décoder depuis base64
        $privateKey = base64_decode($privateKeyBase64);

        if ($privateKey === false) {
            throw new \Exception("Erreur lors du décodage de la clé privée");
        }

        return $privateKey;
    }

    /**
     * Vérifie si une administration possède déjà une paire de clés
     * 
     * @param Administration $administration
     * @return bool
     */
    public function hasKeyPair(Administration $administration): bool
    {
        return !empty($administration->private_key_path) 
            && !empty($administration->cle_publique_ed25519)
            && Storage::disk('local')->exists($administration->private_key_path);
    }

    /**
     * Régénère une paire de clés (en cas de compromission)
     * 
     * @param Administration $administration
     * @return array
     */
    public function regenerateKeyPair(Administration $administration): array
    {
        // Supprimer l'ancienne clé si elle existe
        if ($administration->private_key_path && Storage::disk('local')->exists($administration->private_key_path)) {
            Storage::disk('local')->delete($administration->private_key_path);
            
            Log::warning("Clé privée supprimée lors de la régénération", [
                'administration_id' => $administration->id,
                'old_path' => $administration->private_key_path,
            ]);
        }

        // Générer une nouvelle paire
        return $this->generateKeyPair($administration);
    }

    /**
     * Sécurise les permissions du fichier de clé privée (Unix uniquement)
     * 
     * @param string $path
     * @return void
     */
    protected function secureKeyFile(string $path): void
    {
        // Cette opération ne fonctionne que sur Unix/Linux
        if (DIRECTORY_SEPARATOR === '/') {
            $fullPath = Storage::disk('local')->path($path);
            
            // chmod 600 (lecture/écriture pour le propriétaire uniquement)
            @chmod($fullPath, 0600);
            
            // chmod 700 pour le répertoire parent
            @chmod(dirname($fullPath), 0700);
        }
    }

    /**
     * Supprime complètement les clés d'une administration
     * ATTENTION: Cette opération est irréversible !
     * 
     * @param Administration $administration
     * @return bool
     */
    public function deleteKeyPair(Administration $administration): bool
    {
        if (!$administration->private_key_path) {
            return false;
        }

        // Supprimer le fichier de clé privée
        if (Storage::disk('local')->exists($administration->private_key_path)) {
            Storage::disk('local')->delete($administration->private_key_path);
        }

        // Supprimer le répertoire s'il est vide
        $keyDirectory = "keys/administrations/{$administration->id}";
        if (Storage::disk('local')->exists($keyDirectory) && empty(Storage::disk('local')->files($keyDirectory))) {
            Storage::disk('local')->deleteDirectory($keyDirectory);
        }

        // Mettre à jour l'administration
        $administration->update([
            'cle_publique_ed25519' => null,
            'private_key_path' => null,
        ]);

        Log::warning("Paire de clés supprimée", [
            'administration_id' => $administration->id,
            'administration_nom' => $administration->nom,
        ]);

        return true;
    }
}
