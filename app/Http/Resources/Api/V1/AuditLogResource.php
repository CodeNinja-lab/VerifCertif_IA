<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur' => $this->whenLoaded('utilisateur', fn() => new UserResource($this->utilisateur)),
            'action' => $this->action,
            'objet_type' => $this->objet_type,
            'objet_id' => $this->objet_id,
            'statut' => $this->statut,
            'details' => $this->details,
            'message_erreur' => $this->message_erreur,
            'date_action' => $this->date_action?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

