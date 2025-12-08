<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdministrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type_administration' => $this->type_administration,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'adresse' => $this->adresse,
            'numero_accreditation' => $this->numero_accreditation,
            'email_contact' => $this->email_contact,
            'telephone_contact' => $this->telephone_contact,
            'logo_url' => $this->logo_url,
            'site_web' => $this->site_web,
            'statut' => $this->statut,
            'date_inscription' => $this->date_inscription?->toIso8601String(),
            'documents_count' => $this->when(isset($this->documents_count), $this->documents_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

