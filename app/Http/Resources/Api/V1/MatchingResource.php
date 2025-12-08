<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'offre' => $this->whenLoaded('offre', fn() => new OffreResource($this->offre)),
            'etudiant' => $this->whenLoaded('etudiant', fn() => new UserResource($this->etudiant)),
            'score_global' => (float) $this->score_global,
            'score_competences' => (float) $this->score_competences,
            'score_localisation' => $this->score_localisation ? (float) $this->score_localisation : null,
            'score_experience' => $this->score_experience ? (float) $this->score_experience : null,
            'competences_matchees' => $this->competences_matchees,
            'competences_manquantes' => $this->competences_manquantes,
            'points_forts' => $this->points_forts,
            'points_amelioration' => $this->points_amelioration,
            'algorithme_version' => $this->algorithme_version,
            'notifie' => $this->notifie,
            'date_notification' => $this->date_notification?->toIso8601String(),
            'date_matching' => $this->date_matching?->toIso8601String(),
            'vu_par_etudiant' => $this->vu_par_etudiant,
            'date_vue_etudiant' => $this->date_vue_etudiant?->toIso8601String(),
            'interesse' => $this->interesse,
            'vu_par_recruteur' => $this->vu_par_recruteur,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

