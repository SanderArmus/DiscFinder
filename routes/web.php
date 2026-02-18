<?php

use App\Http\Controllers\StoreFoundDiscController;
use App\Http\Controllers\StoreLostDiscController;
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
            'reportedAt' => $disc->created_at->format('M j, Y'),
        ]);

    return Inertia::render('Dashboard', [
        'discs' => $discs,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('lost-discs', function () {
    return Inertia::render('LostDiscs');
})->middleware(['auth', 'verified'])->name('lost-discs.index');
Route::post('lost-discs', StoreLostDiscController::class)->middleware(['auth', 'verified'])->name('lost-discs.store');

Route::get('found-discs', function () {
    return Inertia::render('FoundDiscs');
})->middleware(['auth', 'verified'])->name('found-discs.index');
Route::post('found-discs', StoreFoundDiscController::class)->middleware(['auth', 'verified'])->name('found-discs.store');

Route::get('about', function () {
    if (auth()->check()) {
        return Inertia::render('About');
    }

    return Inertia::render('AboutPublic');
})->name('about');

require __DIR__.'/settings.php';
