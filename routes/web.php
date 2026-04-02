<?php

use App\Http\Controllers\Admin\AdminDiscsController;
use App\Http\Controllers\Auth\FacebookAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ConfirmMatchController;
use App\Http\Controllers\DeleteDiscController;
use App\Http\Controllers\HandOverMatchController;
use App\Http\Controllers\RejectMatchController;
use App\Http\Controllers\ShowDiscController;
use App\Http\Controllers\ShowHelpController;
use App\Http\Controllers\ShowMatchChatController;
use App\Http\Controllers\ShowMatchDetailsController;
use App\Http\Controllers\StoreFoundDiscController;
use App\Http\Controllers\StoreLostDiscController;
use App\Http\Controllers\StoreMatchMessageController;
use App\Http\Controllers\UpdateDiscController;
use App\Services\MatchChatFinder;
use App\Services\MatchFinder;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('locale/{locale}', function (string $locale) {
    $supported = ['en', 'et'];
    if (! in_array($locale, $supported, true)) {
        abort(400);
    }
    session()->put('locale', $locale);

    return redirect()->back();
})->name('locale.switch');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('auth/Login', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'canRegister' => Features::enabled(Features::registration()),
        'status' => session('status'),
    ]);
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();
    $discs = $user->discs()
        ->with('colors')
        ->latest()
        ->get()
        ->map(fn ($disc) => [
            'id' => $disc->id,
            'name' => $disc->model_name ?: '—',
            'brand' => $disc->plastic_type ?: $disc->manufacturer ?: '—',
            'color' => $disc->colors->pluck('name')->join(', ') ?: '—',
            'status' => $disc->status,
            'matchLifecycle' => $disc->match_lifecycle,
            'active' => (bool) $disc->active,
            'reportedAt' => $disc->created_at->format('M j, Y'),
        ]);

    $matches = app(MatchFinder::class)->findForUser($user, limit: 5, minScore: 60.0);

    return Inertia::render('Dashboard', [
        'discs' => $discs,
        'matches' => $matches,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('messages', function () {
    $user = auth()->user();

    $threads = app(MatchChatFinder::class)->findThreadsForUser($user, limit: 50);

    return Inertia::render('Messages', [
        'threads' => $threads,
    ]);
})->middleware(['auth', 'verified'])->name('messages.index');

Route::get('help', ShowHelpController::class)
    ->middleware(['auth', 'verified'])
    ->name('help');

Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::get('discs', [AdminDiscsController::class, 'index'])->name('admin.discs.index');
    Route::patch('discs/{disc}', [AdminDiscsController::class, 'update'])->name('admin.discs.update');
});

Route::get('lost-discs', function () {
    return Inertia::render('LostDiscs');
})->middleware(['auth', 'verified'])->name('lost-discs.index');
Route::post('lost-discs', StoreLostDiscController::class)->middleware(['auth', 'verified'])->name('lost-discs.store');

Route::get('found-discs', function () {
    return Inertia::render('FoundDiscs');
})->middleware(['auth', 'verified'])->name('found-discs.index');
Route::post('found-discs', StoreFoundDiscController::class)->middleware(['auth', 'verified'])->name('found-discs.store');

Route::get('discs/{disc}', ShowDiscController::class)
    ->middleware(['auth', 'verified'])
    ->name('discs.show');

Route::post('discs/{disc}', UpdateDiscController::class)
    ->middleware(['auth', 'verified'])
    ->name('discs.update');

Route::delete('discs/{disc}', DeleteDiscController::class)
    ->middleware(['auth', 'verified'])
    ->name('discs.destroy');

Route::get('about', function () {
    if (auth()->check()) {
        return Inertia::render('About');
    }

    return Inertia::render('AboutPublic');
})->name('about');

Route::get('auth/facebook/redirect', [FacebookAuthController::class, 'redirectToFacebook'])
    ->middleware(['web'])
    ->name('auth.facebook.redirect');

Route::get('auth/facebook/callback', [FacebookAuthController::class, 'handleFacebookCallback'])
    ->middleware(['web'])
    ->name('auth.facebook.callback');

Route::get('auth/facebook/username', [FacebookAuthController::class, 'showChooseUsername'])
    ->middleware(['web'])
    ->name('auth.facebook.username');

Route::post('auth/facebook/username', [FacebookAuthController::class, 'storeChooseUsername'])
    ->middleware(['web'])
    ->name('auth.facebook.username.store');

Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])
    ->middleware(['web'])
    ->name('auth.google.redirect');

Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])
    ->middleware(['web'])
    ->name('auth.google.callback');

Route::get('auth/google/username', [GoogleAuthController::class, 'showChooseUsername'])
    ->middleware(['web'])
    ->name('auth.google.username');

Route::post('auth/google/username', [GoogleAuthController::class, 'storeChooseUsername'])
    ->middleware(['web'])
    ->name('auth.google.username.store');

Route::get('matches/{match}', ShowMatchChatController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.chat');

Route::get('matches/{match}/details', ShowMatchDetailsController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.details');

Route::post('matches/{match}/messages', StoreMatchMessageController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.messages.store');

Route::post('matches/{match}/confirm', ConfirmMatchController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.confirm');

Route::post('matches/{match}/reject', RejectMatchController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.reject');

Route::post('matches/{match}/handover', HandOverMatchController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.handover');

require __DIR__.'/settings.php';
