<?php

namespace App\Http\Requests;

use App\Models\Disc;
use Illuminate\Foundation\Http\FormRequest;

class DeleteDiscRequest extends FormRequest
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
        return [];
    }
}
