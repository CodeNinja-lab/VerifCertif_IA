<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid_document' => $this->uuid_document,
            'etudiant' => $this->whenLoaded('etudiant', fn() => new UserResource($this->etudiant)),
            'administration' => $this->whenLoaded('administration', fn() => new AdministrationResource($this->administration)),
            'type_document' => $this->type_document,
            'titre' => $this->titre,
            'file_url' => $this->file_url,
            'file_size_kb' => $this->file_size_kb,
            'hash_sha256' => $this->hash_sha256,
            'qr_code_url' => $this->qr_code_url,
            'blockchain_tx_hash' => $this->blockchain_tx_hash,
            'blockchain_network' => $this->blockchain_network,
            'statut' => $this->statut,
            'date_emission' => $this->date_emission?->format('Y-m-d'),
            'date_certification' => $this->date_certification?->toIso8601String(),
            'date_expiration' => $this->date_expiration?->format('Y-m-d'),
            'metadata' => $this->metadata,
            'verification_logs_count' => $this->when(isset($this->verification_logs_count), $this->verification_logs_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

