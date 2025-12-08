<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RevokeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'administration_id' => 'required|exists:administrations,id',
            'motif_categorie' => 'required|in:fraude,erreur_administrative,annulation_diplome,demande_titulaire,autre',
            'motif_detail' => 'required|string',
            'document_justificatif_url' => 'nullable|string|max:500',
            'irreversible' => 'sometimes|boolean',
        ];
    }
}

