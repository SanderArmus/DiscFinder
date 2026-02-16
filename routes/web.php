<?php

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
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
