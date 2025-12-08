<?php

namespace App\Observers;

use App\Models\Administration;
use App\Services\SimpleKeyManagementService;
use Illuminate\Support\Facades\Log;

class AdministrationObserver
{
    protected $keyService;

    public function __construct(SimpleKeyManagementService $keyService)
    {
        $this->keyService = $keyService;
    }

    /**
     * Handle the Administration "created" event.
     * Génère automatiquement une paire de clés Ed25519
     */
    public function created(Administration $administration): void
    {
        try {
            // Générer automatiquement les clés à la création
            $result = $this->keyService->generateKeyPair($administration);
            
            Log::info("Clés Ed25519 générées automatiquement à la création", [
                'administration_id' => $administration->id,
                'administration_nom' => $administration->nom,
                'private_key_path' => $result['private_key_path'],
            ]);

        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer la création
            Log::error("Échec de la génération automatique des clés", [
                'administration_id' => $administration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Administration "updated" event.
     */
    public function updated(Administration $administration): void
    {
        //
    }

    /**
     * Handle the Administration "deleted" event.
     * Supprime les clés associées
     */
    public function deleted(Administration $administration): void
    {
        try {
            // Supprimer les clés lors de la suppression de l'administration
            $this->keyService->deleteKeyPair($administration);

            Log::info("Clés Ed25519 supprimées lors de la suppression de l'administration", [
                'administration_id' => $administration->id,
                'administration_nom' => $administration->nom,
            ]);

        } catch (\Exception $e) {
            Log::error("Échec de la suppression des clés", [
                'administration_id' => $administration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Administration "restored" event.
     */
    public function restored(Administration $administration): void
    {
        //
    }

    /**
     * Handle the Administration "force deleted" event.
     */
    public function forceDeleted(Administration $administration): void
    {
        try {
            // Supprimer définitivement les clés
            $this->keyService->deleteKeyPair($administration);

            Log::warning("Clés Ed25519 supprimées définitivement (force delete)", [
                'administration_id' => $administration->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Échec de la suppression forcée des clés", [
                'administration_id' => $administration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
