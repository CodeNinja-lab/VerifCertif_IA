<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfilCompetenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'competence' => $this->whenLoaded('competence', fn() => new CompetenceResource($this->competence)),
            'competence_id' => $this->competence_id,
            'niveau' => $this->niveau,
            'source' => $this->source,
            'source_document_id' => $this->source_document_id,
            'score_confiance' => $this->score_confiance,
            'annees_experience' => $this->annees_experience,
            'validee_par_etudiant' => $this->validee_par_etudiant,
            'date_extraction' => $this->date_extraction?->toIso8601String(),
            'date_validation' => $this->date_validation?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

