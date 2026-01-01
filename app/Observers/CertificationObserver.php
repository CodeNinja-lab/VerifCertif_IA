<?php

namespace App\Observers;

use App\Models\Certification;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Log;

class CertificationObserver
{
    protected $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Après création d'une certification
     */
    public function created(Certification $certification)
    {
        $this->regenerateEmbedding($certification);
    }

    /**
     * Après mise à jour d'une certification
     */
    public function updated(Certification $certification)
    {
        $this->regenerateEmbedding($certification);
    }

    /**
     * Après suppression d'une certification
     */
    public function deleted(Certification $certification)
    {
        $this->regenerateEmbedding($certification);
    }

    /**
     * Régénérer l'embedding du candidat
     */
    protected function regenerateEmbedding(Certification $certification)
    {
        try {
            if ($certification->utilisateur_id) {
                $userId = $certification->utilisateur_id;
                
                // Régénérer l'embedding de manière asynchrone
                dispatch(function () use ($userId) {
                    app(EmbeddingService::class)->generateCandidateEmbedding($userId);
                })->afterResponse();
            }
        } catch (\Exception $e) {
            Log::error('Error in CertificationObserver', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
