<?php

namespace App\Http\Requests;

use App\Models\MatchThread;
use Illuminate\Foundation\Http\FormRequest;

class StoreMatchMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $match = $this->route('match');

        if (! $user instanceof \App\Models\User) {
            return false;
        }

        if (! $match instanceof MatchThread) {
            return false;
        }

        $match->loadMissing(['lostDisc', 'foundDisc']);

        return $user->id === $match->lostDisc->user_id
            || $user->id === $match->foundDisc->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
        ];
    }
}
