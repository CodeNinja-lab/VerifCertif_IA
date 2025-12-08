<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'nom_normalise' => $this->nom_normalise,
            'categorie' => $this->categorie,
            'description' => $this->description,
            'referentiel_externe_id' => $this->referentiel_externe_id,
            'synonymes' => $this->synonymes,
            'popularite' => $this->popularite,
            'date_creation' => $this->date_creation?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

