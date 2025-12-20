<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'organisme' => $this->organisme,
            'date_obtention' => $this->date_obtention?->format('Y-m-d'),
            'date_expiration' => $this->date_expiration?->format('Y-m-d'),
            'identifiant' => $this->identifiant,
            'url_verification' => $this->url_verification,
            'ordre' => $this->ordre,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
