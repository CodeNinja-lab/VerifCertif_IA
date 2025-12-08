<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document' => $this->whenLoaded('document', fn() => new DocumentResource($this->document)),
            'verificateur_type' => $this->verificateur_type,
            'verificateur' => $this->whenLoaded('verificateur', fn() => new UserResource($this->verificateur)),
            'methode_verification' => $this->methode_verification,
            'resultat' => $this->resultat,
            'details_erreur' => $this->details_erreur,
            'duree_ms' => $this->duree_ms,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'date_verification' => $this->date_verification?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

