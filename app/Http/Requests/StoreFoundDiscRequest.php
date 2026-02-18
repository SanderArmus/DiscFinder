<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoundDiscRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'datetime' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'plastic' => ['nullable', 'string', 'max:100'],
            'customDisc' => ['boolean'],
            'selectedColors' => ['array'],
            'selectedColors.*' => ['string', 'max:50'],
            'condition' => ['nullable', 'string', 'in:new,good,worn'],
            'inscription' => ['nullable', 'string', 'max:500'],
        ];
    }
}
