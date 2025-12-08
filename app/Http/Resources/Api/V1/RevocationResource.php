<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document' => $this->whenLoaded('document', fn() => new DocumentResource($this->document)),
            'administration' => $this->whenLoaded('administration', fn() => new AdministrationResource($this->administration)),
            'operateur' => $this->whenLoaded('operateur', fn() => new UserResource($this->operateur)),
            'motif_categorie' => $this->motif_categorie,
            'motif_detail' => $this->motif_detail,
            'document_justificatif_url' => $this->document_justificatif_url,
            'notification_titulaire' => $this->notification_titulaire,
            'date_notification' => $this->date_notification?->toIso8601String(),
            'date_revocation' => $this->date_revocation?->toIso8601String(),
            'irreversible' => $this->irreversible,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

