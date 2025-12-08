<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_document' => 'required|in:diplome,releve_notes,attestation,certificat,autre',
            'titre' => 'required|string|max:255',
            'file' => 'required_without:generate_pdf|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'file_url' => 'required_without_all:file,generate_pdf|string|max:500',
            'file_size_kb' => 'sometimes|integer|min:0',
            'hash_sha256' => 'required_without_all:file,generate_pdf|string|size:64|unique:documents,hash_sha256',
            'signature_ed25519' => 'required_without_all:file,generate_pdf|string',
            'date_emission' => 'sometimes|date',
            'date_expiration' => 'nullable|date|after:date_emission',
            'metadata' => 'nullable|string', // Accepte JSON string depuis FormData
            // generate_pdf n'est pas validé ici, sera géré manuellement dans le contrôleur
        ];
    }
}

