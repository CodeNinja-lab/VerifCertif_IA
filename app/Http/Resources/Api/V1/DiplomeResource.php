<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiplomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'code' => $this->code,
            'actif' => $this->actif,
            'ordre' => $this->ordre,
            'competences' => $this->whenLoaded('competences', function () {
                return $this->competences->map(function ($competence) {
                    return [
                        'id' => $competence->id,
                        'nom' => $competence->nom,
                        'categorie' => $competence->categorie,
                        'description' => $competence->description,
                        'ordre' => $competence->pivot->ordre ?? 0,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}