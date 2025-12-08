<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdministrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = $this->route('id');

        return [
            'nom' => 'sometimes|string|max:255',
            'type_administration' => 'sometimes|in:universite,ecole,organisme_formation,administration_publique,autre',
            'pays' => 'sometimes|string|max:100',
            'ville' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'numero_accreditation' => 'nullable|string|max:100|unique:administrations,numero_accreditation,' . $adminId,
            'email_contact' => 'sometimes|email|max:255',
            'telephone_contact' => 'nullable|string|max:20',
            'cle_publique_ed25519' => 'sometimes|string',
            'logo_url' => 'nullable|string|max:500',
            'site_web' => 'nullable|url|max:255',
            'statut' => 'sometimes|in:en_attente,verifie,suspendu',
        ];
    }
}

