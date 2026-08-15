<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $requestedLocale = $request->route('locale')
            ?? $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? $request->getPreferredLanguage(self::SUPPORTED)
            ?? config('app.locale');

        $locale = in_array($requestedLocale, self::SUPPORTED, true) ? $requestedLocale : 'en';
        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
