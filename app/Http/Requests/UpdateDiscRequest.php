<?php

namespace App\Http\Requests;

use App\Models\Disc;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $discParam = $this->route('disc');

        if ($user === null) {
            return false;
        }

        $disc = $discParam instanceof Disc ? $discParam : Disc::query()->find($discParam);
        if ($disc === null) {
            return false;
        }

        if ($user->id !== $disc->user_id) {
            return false;
        }

        // Once confirmed/handed over, discs are locked.
        return $disc->active === true;
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
            'selectedColors' => ['array'],
            'selectedColors.*' => ['string', 'max:50'],
            'condition' => ['nullable', 'string', 'in:new,good,worn'],
            'inscription' => ['nullable', 'string', 'max:500'],
        ];
    }
}
