<?php

namespace App\Observers;

use App\Models\Offre;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Log;

class OffreObserver
{
    protected $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Après création d'une offre
     */
    public function created(Offre $offre)
    {
        // Générer l'embedding uniquement si l'offre est publiée
        if ($offre->statut === 'PUBLIEE') {
            $this->generateEmbedding($offre);
        }
    }

    /**
     * Après mise à jour d'une offre
     */
    public function updated(Offre $offre)
    {
        if ($offre->statut === 'PUBLIEE') {
            // Régénérer l'embedding si l'offre est publiée
            $this->generateEmbedding($offre);
        } else {
            // Supprimer l'embedding si l'offre n'est plus publiée
            $this->deleteEmbedding($offre);
        }
    }

    /**
     * Après suppression d'une offre
     */
    public function deleted(Offre $offre)
    {
        $this->deleteEmbedding($offre);
    }

    /**
     * Générer ou régénérer l'embedding d'une offre
     */
    protected function generateEmbedding(Offre $offre)
    {
        try {
            // Générer l'embedding de manière asynchrone
            dispatch(function () use ($offre) {
                app(EmbeddingService::class)->generateJobOfferEmbedding($offre->id);
            })->afterResponse();
        } catch (\Exception $e) {
            Log::error('Error generating embedding in OffreObserver', [
                'offre_id' => $offre->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Supprimer l'embedding d'une offre
     */
    protected function deleteEmbedding(Offre $offre)
    {
        try {
            // Supprimer l'embedding de manière asynchrone
            dispatch(function () use ($offre) {
                app(EmbeddingService::class)->deleteJobOfferEmbedding($offre->id);
            })->afterResponse();
        } catch (\Exception $e) {
            Log::error('Error deleting embedding in OffreObserver', [
                'offre_id' => $offre->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
