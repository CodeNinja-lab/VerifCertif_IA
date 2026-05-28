<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncExternalOffersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_name' => 'required|string|max:255',
            'source_account' => 'required|string|max:255',
            'mark_missing_as_expired' => 'sometimes|boolean',
            'offers' => 'required|array|min:1',
            'offers.*.external_id' => 'required|string|max:255',
            'offers.*.title' => 'required|string|max:255',
            'offers.*.company' => 'required|string|max:255',
            'offers.*.location' => 'nullable|string|max:255',
            'offers.*.description' => 'required|string',
            'offers.*.contract_type' => 'nullable|string|in:CDI,CDD,stage,alternance,freelance,interim',
            'offers.*.remote_type' => 'nullable|string|in:non,partiel,total',
            'offers.*.salary_min' => 'nullable|integer|min:0',
            'offers.*.salary_max' => 'nullable|integer|min:0',
            'offers.*.currency' => 'sometimes|string|size:3',
            'offers.*.expires_at' => 'nullable|date',
            'offers.*.source_url' => 'nullable|url',
            'offers.*.metadata' => 'required|array',
            'offers.*.metadata.source_name' => 'required|string|max:255',
            'offers.*.metadata.source_account' => 'required|string|max:255',
            'offers.*.metadata.source_id' => 'required|string|max:255',
            'offers.*.metadata.source_url' => 'nullable|url',
            'offers.*.extra' => 'sometimes|array',
        ];
    }
}