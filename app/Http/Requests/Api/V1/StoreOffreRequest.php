<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'missions_principales' => 'nullable|string',
            'profil_recherche' => 'nullable|string',
            'nice_to_have' => 'nullable|string',
            'avantages' => 'nullable|array',
            'avantages.*' => 'string',
            'processus_recrutement' => 'nullable|array',
            'processus_recrutement.*.step' => 'required_with:processus_recrutement|integer',
            'processus_recrutement.*.title' => 'required_with:processus_recrutement|string',
            'processus_recrutement.*.duration' => 'required_with:processus_recrutement|string',
            'entreprise' => 'required|string|max:255',
            'secteur_activite' => 'nullable|string|max:100',
            'lieu' => 'required|string|max:255',
            'type_contrat' => 'required|string|in:CDI,CDD,stage,alternance,freelance,interim',
            'duree_contrat_mois' => 'nullable|integer|min:1',
            'teletravail' => 'nullable|string|in:non,partiel,total',
            'salaire_min' => 'nullable|integer|min:0',
            'salaire_max' => 'nullable|integer|min:0|gte:salaire_min',
            'devise' => 'sometimes|string|size:3',
            'niveau_etudes_requis' => 'nullable|string|in:bac,bac+2,bac+3,bac+5,bac+8,sans_diplome',
            'annees_experience_min' => 'nullable|integer|min:0',
            'date_expiration' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre du poste est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'entreprise.required' => 'Le nom de l\'entreprise est obligatoire.',
            'lieu.required' => 'La localisation est obligatoire.',
            'type_contrat.required' => 'Le type de contrat est obligatoire.',
            'type_contrat.in' => 'Le type de contrat doit être l\'un des suivants: CDI, CDD, stage, alternance, freelance, interim.',
            'salaire_max.gte' => 'Le salaire maximum doit être supérieur ou égal au salaire minimum.',
        ];
    }
}

