<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->banned_at !== null) {
            $routeName = Route::currentRouteName();

            if (! in_array($routeName, ['banned', 'logout'], true)) {
                return redirect()->route('banned');
            }
        }

        return $next($request);
    }
}
