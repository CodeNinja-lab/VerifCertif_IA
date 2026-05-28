<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recruteur' => $this->whenLoaded('recruteur', fn() => new UserResource($this->recruteur)),
            'titre' => $this->titre,
            'description' => $this->description,
            'missions_principales' => $this->missions_principales,
            'profil_recherche' => $this->profil_recherche,
            'nice_to_have' => $this->nice_to_have,
            'avantages' => $this->avantages,
            'processus_recrutement' => $this->processus_recrutement,
            'entreprise' => $this->entreprise,
            'secteur_activite' => $this->secteur_activite,
            'lieu' => $this->lieu,
            'type_contrat' => $this->type_contrat,
            'duree_contrat_mois' => $this->duree_contrat_mois,
            'teletravail' => $this->teletravail,
            'salaire_min' => $this->salaire_min,
            'salaire_max' => $this->salaire_max,
            'devise' => $this->devise,
            'niveau_etudes_requis' => $this->niveau_etudes_requis,
            'annees_experience_min' => $this->annees_experience_min,
            'date_publication' => $this->date_publication?->toIso8601String(),
            'date_expiration' => $this->date_expiration?->format('Y-m-d'),
            'statut' => $this->statut,
            'source_name' => $this->source_name,
            'source_account' => $this->source_account,
            'source_external_id' => $this->source_external_id,
            'source_url' => $this->source_url,
            'source_imported_at' => $this->source_imported_at?->toIso8601String(),
            'source_last_seen_at' => $this->source_last_seen_at?->toIso8601String(),
            'nombre_vues' => $this->nombre_vues,
            'nombre_candidatures' => $this->nombre_candidatures,
            'candidatures_count' => $this->when(isset($this->candidatures_count), fn() => $this->candidatures_count),
            'matchings_count' => $this->when(isset($this->matchings_count), fn() => $this->matchings_count),
            'competences' => $this->whenLoaded('offreCompetences', fn() => OffreCompetenceResource::collection($this->offreCompetences)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

