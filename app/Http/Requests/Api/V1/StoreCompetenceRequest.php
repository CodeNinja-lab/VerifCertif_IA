<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompetenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'nom_normalise' => 'sometimes|string|max:255',
            'categorie' => 'required|in:technique,transversale,langue,logiciel,framework,domaine,autre',
            'description' => 'nullable|string',
            'referentiel_externe_id' => 'nullable|string|max:100',
            'synonymes' => 'nullable|array',
        ];
    }
}

