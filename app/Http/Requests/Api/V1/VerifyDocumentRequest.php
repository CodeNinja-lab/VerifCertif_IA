<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required_without:hash_sha256|string|size:36',
            'hash_sha256' => 'required_without:uuid|string|size:64',
        ];
    }
}

