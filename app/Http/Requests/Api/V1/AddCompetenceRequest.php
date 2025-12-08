<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AddCompetenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competence_id' => 'required|exists:competences,id',
            'niveau' => 'nullable|in:debutant,intermediaire,avance,expert',
            'source' => 'sometimes|in:ia_extraction,manuel,import_cv,validation_admin',
            'source_document_id' => 'nullable|exists:documents,id',
            'score_confiance' => 'nullable|numeric|min:0|max:100',
            'annees_experience' => 'nullable|numeric|min:0|max:50',
            'validee_par_etudiant' => 'sometimes|boolean',
        ];
    }
}

