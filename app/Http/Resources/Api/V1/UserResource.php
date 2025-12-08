<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'nom_entreprise' => $this->nom_entreprise,
            'name' => trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')),
            'email' => $this->email,
            'role' => $this->role,
            'telephone' => $this->telephone,
            'photo_url' => $this->photo_url,
            'langue' => $this->langue,
            'is_active' => $this->is_active,
            'date_creation' => $this->date_creation?->toIso8601String(),
            'derniere_connexion' => $this->derniere_connexion?->toIso8601String(),
            'profil_etudiant' => $this->whenLoaded('profilEtudiant', fn() => new ProfilEtudiantResource($this->profilEtudiant)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

