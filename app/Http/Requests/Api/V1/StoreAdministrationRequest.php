<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'type_administration' => 'required|in:universite,ecole,organisme_formation,administration_publique,autre',
            'pays' => 'required|string|max:100',
            'ville' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'numero_accreditation' => 'nullable|string|max:100|unique:administrations,numero_accreditation',
            'email_contact' => 'required|email|max:255',
            'telephone_contact' => 'nullable|string|max:20',
            'cle_publique_ed25519' => 'required|string',
            'logo_url' => 'nullable|string|max:500',
            'site_web' => 'nullable|url|max:255',
        ];
    }
}

