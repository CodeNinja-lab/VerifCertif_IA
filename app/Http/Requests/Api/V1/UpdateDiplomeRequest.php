<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiplomeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'administration']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $diplomeId = $this->route('id');
        
        return [
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:100|unique:diplomes,code,' . $diplomeId,
            'actif' => 'sometimes|boolean',
            'ordre' => 'sometimes|integer|min:0',
            'competences' => 'sometimes|array|min:1',
            'competences.*' => 'exists:competences,id',
        ];
    }
}