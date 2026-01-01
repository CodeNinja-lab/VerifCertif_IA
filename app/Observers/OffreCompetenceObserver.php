<?php

namespace App\Observers;

use App\Models\OffreCompetence;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Log;

class OffreCompetenceObserver
{
    protected $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Après création d'une compétence d'offre
     */
    public function created(OffreCompetence $offreCompetence)
    {
        $this->regenerateEmbedding($offreCompetence);
    }

    /**
     * Après mise à jour d'une compétence d'offre
     */
    public function updated(OffreCompetence $offreCompetence)
    {
        $this->regenerateEmbedding($offreCompetence);
    }

    /**
     * Après suppression d'une compétence d'offre
     */
    public function deleted(OffreCompetence $offreCompetence)
    {
        $this->regenerateEmbedding($offreCompetence);
    }

    /**
     * Régénérer l'embedding de l'offre
     */
    protected function regenerateEmbedding(OffreCompetence $offreCompetence)
    {
        try {
            if ($offreCompetence->offre_id) {
                $offre = $offreCompetence->offre;
                
                // Régénérer seulement si l'offre est publiée
                if ($offre && $offre->statut === 'PUBLIEE') {
                    dispatch(function () use ($offre) {
                        app(EmbeddingService::class)->generateJobOfferEmbedding($offre->id);
                    })->afterResponse();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in OffreCompetenceObserver', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
