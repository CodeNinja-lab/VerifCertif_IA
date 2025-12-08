<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfilEtudiantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur' => $this->whenLoaded('utilisateur', fn() => new UserResource($this->utilisateur)),
            'bio' => $this->bio,
            'cv_url' => $this->cv_url,
            'linkedin_url' => $this->linkedin_url,
            'github_url' => $this->github_url,
            'portfolio_url' => $this->portfolio_url,
            'disponibilite' => $this->disponibilite,
            'localisation_actuelle' => $this->localisation_actuelle,
            'localisation_souhaitee' => $this->localisation_souhaitee,
            'mobilite' => $this->mobilite,
            'salaire_minimum_souhaite' => $this->salaire_minimum_souhaite,
            'types_contrat_souhaites' => $this->types_contrat_souhaites,
            'profil_public' => $this->profil_public,
            'date_mise_a_jour' => $this->date_mise_a_jour?->toIso8601String(),
            'competences' => $this->whenLoaded('profilCompetences', fn() => ProfilCompetenceResource::collection($this->profilCompetences)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

