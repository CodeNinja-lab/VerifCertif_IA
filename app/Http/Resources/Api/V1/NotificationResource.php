<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'priorite' => $this->priorite,
            'titre' => $this->titre,
            'message' => $this->message,
            'lien_action' => $this->lien_action,
            'icone' => $this->icone,
            'lue' => $this->lue,
            'date_lecture' => $this->date_lecture?->toIso8601String(),
            'archivee' => $this->archivee,
            'date_envoi' => $this->date_envoi?->toIso8601String(),
            'date_expiration' => $this->date_expiration?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

