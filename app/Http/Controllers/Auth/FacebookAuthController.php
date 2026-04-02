<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class FacebookAuthController extends Controller
{
    public function redirectToFacebook(Request $request): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        $providerUser = Socialite::driver('facebook')->user();

        $facebookId = (string) $providerUser->getId();
        $email = $providerUser->getEmail();

        if (! is_string($email) || $email === '') {
            return redirect()
                ->route('home')
                ->with('status', 'Facebook did not return an email address.');
        }

        $hasFacebookIdColumn = Schema::hasColumn('users', 'facebook_id');

        $user = User::query()->where('facebook_id', $facebookId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user) {
            $attributes = [
                'email_verified_at' => $user->email_verified_at ?? now(),
            ];

            if ($hasFacebookIdColumn) {
                $attributes['facebook_id'] = $facebookId;
            }

            $user->forceFill($attributes)->save();

            Auth::login($user);

            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        Session::put('auth.facebook', [
            'id' => $facebookId,
            'email' => $email,
        ]);

        return redirect()->route('auth.facebook.username');
    }

    public function showChooseUsername(): \Inertia\Response
    {
        $facebook = Session::get('auth.facebook');

        if (! is_array($facebook) || ! isset($facebook['email'], $facebook['id'])) {
            return Inertia::render('auth/FacebookUsername', [
                'email' => null,
            ]);
        }

        return Inertia::render('auth/FacebookUsername', [
            'email' => $facebook['email'],
        ]);
    }

    public function storeChooseUsername(Request $request): RedirectResponse
    {
        $facebook = Session::pull('auth.facebook');

        if (! is_array($facebook) || ! isset($facebook['email'], $facebook['id'])) {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                'unique:users,username',
            ],
        ]);

        $hasFacebookIdColumn = Schema::hasColumn('users', 'facebook_id');

        $attributes = [
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $facebook['email'],
            'password' => Str::random(32),
        ];

        if ($hasFacebookIdColumn) {
            $attributes['facebook_id'] = $facebook['id'];
        }

        $user = User::create($attributes);

        $user->email_verified_at = now();
        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
