<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     *
     * @var array<string>
     */
    protected array $locales = ['en', 'et'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // A signed-in user's saved preference wins so the language follows them
        // across devices; guests fall back to the session, then the app default.
        $locale = $user?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (in_array($locale, $this->locales, true)) {
            app()->setLocale($locale);

            if ($request->session()->get('locale') !== $locale) {
                $request->session()->put('locale', $locale);
            }
        }

        return $next($request);
    }
}
