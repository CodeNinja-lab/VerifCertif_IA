<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etudiant_id' => $this->etudiant_id,
            'offre_id' => $this->offre_id,
            'statut' => $this->statut,
            'statut_label' => $this->statut_label,
            'lettre_motivation' => $this->lettre_motivation,
            'cv_url' => $this->cv_url,
            'date_candidature' => $this->date_candidature?->toIso8601String(),
            'date_entretien' => $this->date_entretien?->toIso8601String(),
            'feedback' => $this->feedback,
            'offre' => $this->whenLoaded('offre', fn() => [
                'id' => $this->offre->id,
                'titre' => $this->offre->titre,
                'entreprise' => $this->offre->entreprise,
                'lieu' => $this->offre->lieu,
                'type_contrat' => $this->offre->type_contrat,
                'salaire_min' => $this->offre->salaire_min,
                'salaire_max' => $this->offre->salaire_max,
                'devise' => $this->offre->devise,
                'teletravail' => $this->offre->teletravail,
                'statut' => $this->offre->statut,
            ]),
            'etudiant' => $this->whenLoaded('etudiant', fn() => new UserResource($this->etudiant)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
