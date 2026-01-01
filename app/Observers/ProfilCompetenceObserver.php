<?php

namespace App\Observers;

use App\Models\ProfilCompetence;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Log;

class ProfilCompetenceObserver
{
    protected $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Après création d'une compétence
     */
    public function created(ProfilCompetence $profilCompetence)
    {
        $this->regenerateEmbedding($profilCompetence);
    }

    /**
     * Après mise à jour d'une compétence
     */
    public function updated(ProfilCompetence $profilCompetence)
    {
        $this->regenerateEmbedding($profilCompetence);
    }

    /**
     * Après suppression d'une compétence
     */
    public function deleted(ProfilCompetence $profilCompetence)
    {
        $this->regenerateEmbedding($profilCompetence);
    }

    /**
     * Régénérer l'embedding du candidat
     */
    protected function regenerateEmbedding(ProfilCompetence $profilCompetence)
    {
        try {
            if ($profilCompetence->profilEtudiant && $profilCompetence->profilEtudiant->utilisateur_id) {
                $userId = $profilCompetence->profilEtudiant->utilisateur_id;
                
                // Régénérer l'embedding de manière asynchrone pour ne pas bloquer la requête
                dispatch(function () use ($userId) {
                    app(EmbeddingService::class)->generateCandidateEmbedding($userId);
                })->afterResponse();
            }
        } catch (\Exception $e) {
            Log::error('Error in ProfilCompetenceObserver', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
