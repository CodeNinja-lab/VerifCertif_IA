<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'prenom' => 'sometimes|string|max:100',
            'nom' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $userId . '|max:255',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:etudiant,recruteur,administration,admin',
            'telephone' => 'nullable|string|max:20',
            'langue' => 'sometimes|string|max:5',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

