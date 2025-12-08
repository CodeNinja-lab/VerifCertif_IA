<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'required|string|max:20',
            'nom_universite' => 'required|string|max:255',
            'adresse_universite' => 'required|string|max:255',
            'code_acces' => 'required|string|exists:admin_access_codes,code',
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'nom_universite.required' => 'Le nom de l\'université est obligatoire.',
            'adresse_universite.required' => 'L\'adresse de l\'université est obligatoire.',
            'code_acces.required' => 'Le code d\'accès est obligatoire.',
            'code_acces.exists' => 'Le code d\'accès est invalide.',
        ];
    }
}


