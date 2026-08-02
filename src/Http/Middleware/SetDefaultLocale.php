<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultLocale extends LocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('full-translation.default_locale', 'en'));

        return $next($request);
    }
}
