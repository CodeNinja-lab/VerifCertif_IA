<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenom' => 'sometimes|string|max:100',
            'nom' => 'sometimes|string|max:100',
            'nom_entreprise' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'photo_url' => 'sometimes|url|max:500',
            'langue' => 'sometimes|string|in:fr,en,es,ar|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'prenom.max' => 'Le prénom ne doit pas dépasser 100 caractères.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'nom.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'telephone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'telephone.max' => 'Le téléphone ne doit pas dépasser 20 caractères.',
            'photo_url.url' => 'L\'URL de la photo doit être valide.',
            'photo_url.max' => 'L\'URL de la photo ne doit pas dépasser 500 caractères.',
            'langue.in' => 'La langue doit être fr, en, es ou ar.',
        ];
    }
}

