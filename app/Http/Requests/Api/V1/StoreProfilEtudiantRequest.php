<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfilEtudiantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio' => 'nullable|string',
            'cv_url' => 'nullable|url|max:500',
            'linkedin_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'disponibilite' => 'nullable|in:immediat,1_mois,3_mois,non_disponible',
            'localisation_actuelle' => 'nullable|string|max:255',
            'localisation_souhaitee' => 'nullable|array',
            'mobilite' => 'nullable|in:locale,nationale,internationale,teletravail',
            'salaire_minimum_souhaite' => 'nullable|integer|min:0',
            'types_contrat_souhaites' => 'nullable|array',
            'profil_public' => 'sometimes|boolean',
        ];
    }
}

