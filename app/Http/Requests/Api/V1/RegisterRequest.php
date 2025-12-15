<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:etudiant,recruteur',
            'telephone' => 'required|string|max:20',
            'langue' => 'sometimes|string|in:fr,en,es,ar|max:5',
        ];

        // Le numéro étudiant est requis pour les étudiants
        if ($this->input('role') === 'etudiant') {
            $rules['numero_etudiant'] = 'required|string|max:50|unique:users,numero_etudiant';
        }

        // Le nom de l'entreprise est requis uniquement pour les recruteurs
        if ($this->input('role') === 'recruteur') {
            $rules['nom_entreprise'] = 'required|string|max:255';
        }

        return $rules;
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
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être etudiant ou recruteur.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'langue.in' => 'La langue doit être fr, en, es ou ar.',
            'numero_etudiant.required' => 'Le numéro étudiant est obligatoire.',
            'numero_etudiant.unique' => 'Ce numéro étudiant est déjà utilisé.',
            'numero_etudiant.max' => 'Le numéro étudiant ne peut pas dépasser 50 caractères.',
        ];
    }
}

