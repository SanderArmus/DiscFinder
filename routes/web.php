<?php

use App\Http\Controllers\Admin\AdminDiscsController;
use App\Http\Controllers\Admin\AdminSupportMessagesController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Auth\FacebookAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ConfirmMatchController;
use App\Http\Controllers\DeleteDiscController;
use App\Http\Controllers\EndChatController;
use App\Http\Controllers\EndSupportChatController;
use App\Http\Controllers\HandOverMatchController;
use App\Http\Controllers\RejectMatchController;
use App\Http\Controllers\ShowDiscController;
use App\Http\Controllers\ShowHelpController;
use App\Http\Controllers\ShowMatchChatController;
use App\Http\Controllers\ShowMatchDetailsController;
use App\Http\Controllers\ShowSupportChatController;
use App\Http\Controllers\StoreAdminMessageController;
use App\Http\Controllers\StoreChatReportController;
use App\Http\Controllers\StoreFoundDiscController;
use App\Http\Controllers\StoreLostDiscController;
use App\Http\Controllers\StoreMatchMessageController;
use App\Http\Controllers\StoreSupportMessageController;
use App\Http\Controllers\UpdateDiscController;
use App\Models\Disc;
use App\Services\MatchChatFinder;
use App\Services\MatchFinder;
use Illuminate\Http\Request;
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

    // Support thread (messages with match_id = null)
    $admin = \App\Models\User::query()->where('role', 'admin')->orderBy('id')->first();
    $supportThread = null;
    if ($admin !== null) {
        $blocked = \App\Models\ChatBlock::query()
            ->whereNull('match_id')
            ->where(function ($q) use ($user, $admin) {
                $q->where(function ($q2) use ($user, $admin) {
                    $q2->where('blocker_id', $user->id)->where('blocked_id', $admin->id);
                })->orWhere(function ($q2) use ($user, $admin) {
                    $q2->where('blocker_id', $admin->id)->where('blocked_id', $user->id);
                });
            })
            ->exists();

        $lastSupportMessage = \App\Models\Message::query()
            ->whereNull('match_id')
            ->where(function ($q) use ($user, $admin) {
                $q->where(function ($q2) use ($user, $admin) {
                    $q2->where('sender_id', $user->id)->where('receiver_id', $admin->id);
                })->orWhere(function ($q2) use ($user, $admin) {
                    $q2->where('sender_id', $admin->id)->where('receiver_id', $user->id);
                });
            })
            ->latest('created_at')
            ->first();

        if ($lastSupportMessage !== null) {
            $supportThread = [
                'otherUserName' => $admin->username ?: ($admin->name ?: 'Admin'),
                'lastMessagePreview' => \Illuminate\Support\Str::limit((string) $lastSupportMessage->content, 80, '...'),
                'lastMessageAt' => $lastSupportMessage->created_at?->format('M j, H:i') ?? '',
                'blocked' => $blocked,
            ];
        }
    }

    return Inertia::render('Messages', [
        'threads' => $threads,
        'supportThread' => $supportThread,
    ]);
})->middleware(['auth', 'verified'])->name('messages.index');

Route::get('support/chat', ShowSupportChatController::class)
    ->middleware(['auth', 'verified'])
    ->name('support.chat');

Route::post('support/messages', StoreSupportMessageController::class)
    ->middleware(['auth', 'verified'])
    ->name('support.messages.store');

Route::post('support/chat/end', EndSupportChatController::class)
    ->middleware(['auth', 'verified'])
    ->name('support.chat.end');

Route::post('chat-reports', StoreChatReportController::class)
    ->middleware(['auth', 'verified'])
    ->name('chat-reports.store');

Route::get('help', ShowHelpController::class)
    ->middleware(['auth', 'verified'])
    ->name('help');

Route::post('help/message-to-admin', StoreAdminMessageController::class)
    ->middleware(['auth', 'verified'])
    ->name('help.admin-message.store');

Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::get('discs', [AdminDiscsController::class, 'index'])->name('admin.discs.index');
    Route::patch('discs/{disc}', [AdminDiscsController::class, 'update'])->name('admin.discs.update');
    Route::patch('matches/{match}', [AdminDiscsController::class, 'updateMatch'])->name('admin.matches.update');

    Route::get('users', [AdminUsersController::class, 'index'])->name('admin.users.index');
    Route::patch('users/{user}', [AdminUsersController::class, 'update'])->name('admin.users.update');

    Route::get('support-messages', [AdminSupportMessagesController::class, 'index'])
        ->name('admin.support-messages.index');
    Route::post('support-messages/{message}/reply', [AdminSupportMessagesController::class, 'reply'])
        ->name('admin.support-messages.reply');

    Route::get('chat-reports', [\App\Http\Controllers\Admin\ChatReportsController::class, 'index'])
        ->name('admin.chat-reports.index');
});

Route::get('lost-discs', function () {
    return Inertia::render('LostDiscs');
})->middleware(['auth', 'verified'])->name('lost-discs.index');
Route::post('lost-discs', StoreLostDiscController::class)->middleware(['auth', 'verified', 'throttle:disc-submissions'])->name('lost-discs.store');

Route::get('found-discs', function () {
    return Inertia::render('FoundDiscs');
})->middleware(['auth', 'verified'])->name('found-discs.index');
Route::post('found-discs', StoreFoundDiscController::class)->middleware(['auth', 'verified', 'throttle:disc-submissions'])->name('found-discs.store');

Route::get('catalog/manufacturers', function (Request $request) {
    $q = trim((string) $request->query('q', ''));

    $query = Disc::query()
        ->select('manufacturer')
        ->whereNotNull('manufacturer')
        ->distinct();

    if ($q !== '') {
        $query->where('manufacturer', 'like', '%'.$q.'%');
    }

    $items = $query
        ->orderBy('manufacturer')
        ->limit(10)
        ->pluck('manufacturer')
        ->values();

    return response()->json(['items' => $items]);
})->middleware(['auth', 'verified']);

Route::get('catalog/plastics', function (Request $request) {
    $manufacturer = (string) $request->query('manufacturer', '');
    $q = (string) $request->query('q', '');

    $query = Disc::query()
        ->select('plastic_type')
        ->whereNotNull('plastic_type')
        ->distinct();

    if ($manufacturer !== '' && $manufacturer !== 'other') {
        $query->where('manufacturer', $manufacturer);
    }

    $q = trim($q);
    if ($q !== '') {
        $query->where('plastic_type', 'like', '%'.$q.'%');
    }

    $items = $query
        ->orderBy('plastic_type')
        ->limit(10)
        ->pluck('plastic_type')
        ->values();

    return response()->json(['items' => $items]);
})->middleware(['auth', 'verified']);

Route::get('catalog/models', function (Request $request) {
    $manufacturer = (string) $request->query('manufacturer', '');
    $plastic = (string) $request->query('plastic', '');
    $q = (string) $request->query('q', '');

    $query = Disc::query()
        ->select('model_name')
        ->whereNotNull('model_name')
        ->distinct();

    if ($manufacturer !== '' && $manufacturer !== 'other') {
        $query->where('manufacturer', $manufacturer);
    }

    $plastic = trim($plastic);
    if ($plastic !== '') {
        $query->where('plastic_type', 'like', '%'.$plastic.'%');
    }

    $q = trim($q);
    if ($q !== '') {
        $query->where('model_name', 'like', '%'.$q.'%');
    }

    $items = $query
        ->orderBy('model_name')
        ->limit(10)
        ->pluck('model_name')
        ->values();

    return response()->json(['items' => $items]);
})->middleware(['auth', 'verified']);

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

Route::post('matches/{match}/end', EndChatController::class)
    ->middleware(['auth', 'verified'])
    ->name('matches.end');

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
