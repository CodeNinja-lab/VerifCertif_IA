<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffreCompetenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'competence' => $this->whenLoaded('competence', fn() => new CompetenceResource($this->competence)),
            'competence_id' => $this->competence_id,
            'niveau_requis' => $this->niveau_requis,
            'importance' => $this->importance,
            'poids' => $this->poids,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

