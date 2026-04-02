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

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $providerUser = Socialite::driver('google')->user();

        $googleId = (string) $providerUser->getId();
        $email = $providerUser->getEmail();

        $email = is_string($email) && $email !== '' ? $email : "google_{$googleId}@discivo.local";

        $hasGoogleIdColumn = Schema::hasColumn('users', 'google_id');

        $user = null;

        if ($hasGoogleIdColumn) {
            $user = User::query()->where('google_id', $googleId)->first();
        }

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user) {
            $attributes = [
                'email_verified_at' => $user->email_verified_at ?? now(),
            ];

            if ($hasGoogleIdColumn) {
                $attributes['google_id'] = $googleId;
            }

            $user->forceFill($attributes)->save();

            Auth::login($user);

            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        Session::put('auth.google', [
            'id' => $googleId,
            'email' => $email,
        ]);

        return redirect()->route('auth.google.username');
    }

    public function showChooseUsername(): \Inertia\Response
    {
        $google = Session::get('auth.google');

        if (! is_array($google) || ! isset($google['email'], $google['id'])) {
            return Inertia::render('auth/GoogleUsername', [
                'email' => null,
            ]);
        }

        return Inertia::render('auth/GoogleUsername', [
            'email' => $google['email'],
        ]);
    }

    public function storeChooseUsername(Request $request): RedirectResponse
    {
        $google = Session::pull('auth.google');

        if (! is_array($google) || ! isset($google['email'], $google['id'])) {
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

        $hasGoogleIdColumn = Schema::hasColumn('users', 'google_id');

        $attributes = [
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $google['email'],
            'password' => Str::random(32),
        ];

        if ($hasGoogleIdColumn) {
            $attributes['google_id'] = $google['id'];
        }

        $user = User::create($attributes);

        $user->email_verified_at = now();
        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
