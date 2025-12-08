<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AddCompetenceToOffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competence_id' => 'required|exists:competences,id',
            'niveau_requis' => 'nullable|in:debutant,intermediaire,avance,expert',
            'importance' => 'sometimes|in:indispensable,importante,souhaitee,bonus',
            'poids' => 'sometimes|integer|min:1|max:10',
        ];
    }
}

