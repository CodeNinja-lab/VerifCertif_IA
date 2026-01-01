<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiplomeRequest extends FormRequest
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
        $rules = [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'actif' => 'sometimes|boolean',
            'ordre' => 'sometimes|integer|min:0',
            'competences' => 'required|array|min:1',
            'competences.*' => 'required|integer|exists:competences,id',
        ];

        // Validation unique pour le code seulement s'il n'est pas vide
        if ($this->has('code') && !empty($this->code)) {
            $rules['code'] = 'string|max:100|unique:diplomes,code';
        } else {
            $rules['code'] = 'nullable|string|max:100';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $data = [];
        
        // Convertir actif en boolean si nécessaire
        if ($this->has('actif')) {
            $actifValue = $this->actif;
            if (is_string($actifValue)) {
                $actifValue = filter_var($actifValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            $data['actif'] = $actifValue !== null ? $actifValue : true;
        }

        // Convertir ordre en integer si nécessaire
        if ($this->has('ordre')) {
            $data['ordre'] = (int) $this->ordre;
        }

        // S'assurer que les compétences sont des entiers
        if ($this->has('competences') && is_array($this->competences)) {
            $competences = [];
            foreach ($this->competences as $id) {
                $competences[] = (int) $id;
            }
            $data['competences'] = $competences;
        }

        // Convertir code vide en null
        if ($this->has('code')) {
            $codeValue = $this->code;
            if (empty($codeValue) || (is_string($codeValue) && trim($codeValue) === '')) {
                $data['code'] = null;
            }
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
}