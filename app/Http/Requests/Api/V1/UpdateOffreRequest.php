<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'entreprise' => 'sometimes|string|max:255',
            'secteur_activite' => 'nullable|string|max:100',
            'lieu' => 'sometimes|string|max:255',
            'type_contrat' => 'sometimes|in:CDI,CDD,stage,alternance,freelance,interim',
            'duree_contrat_mois' => 'nullable|integer|min:1',
            'teletravail' => 'nullable|in:non,partiel,total',
            'salaire_min' => 'nullable|integer|min:0',
            'salaire_max' => 'nullable|integer|min:0|gte:salaire_min',
            'devise' => 'sometimes|string|size:3',
            'niveau_etudes_requis' => 'nullable|in:bac,bac+2,bac+3,bac+5,bac+8,sans_diplome',
            'annees_experience_min' => 'nullable|integer|min:0',
            'date_expiration' => 'nullable|date|after:today',
            'statut' => 'sometimes|in:BROUILLON,PUBLIEE,EXPIREE,POURVUE,ARCHIVEE',
        ];
    }
}

