<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'email' => $this->emailRules(),
            'username' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique(User::class, 'username'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'username' => $input['username'],
            'name' => $input['username'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
