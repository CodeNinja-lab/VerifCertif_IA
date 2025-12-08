<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|string|max:255',
            'nom_normalise' => 'sometimes|string|max:255',
            'categorie' => 'sometimes|in:technique,transversale,langue,logiciel,framework,domaine,autre',
            'description' => 'nullable|string',
            'referentiel_externe_id' => 'nullable|string|max:100',
            'synonymes' => 'nullable|array',
        ];
    }
}

