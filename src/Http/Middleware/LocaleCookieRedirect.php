<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;
use Symfony\Component\HttpFoundation\Response;

class LocaleCookieRedirect extends LocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $params = explode('/', $request->path());
        $locale = $request->cookie(config('full-translation.cookie_name', 'locale'), false);

        if (count($params) > 0 && FullLocalization::checkLocaleInSupportedLocales($params[0])) {
            return $next($request)->withCookie(cookie()->forever(config('full-translation.cookie_name', 'locale'), $params[0]));
        }

        if (empty($locale)) {
            $locale = FullLocalization::getLocale();
        }

        if (
            $locale &&
            FullLocalization::checkLocaleInSupportedLocales($locale) &&
            ! FullLocalization::isHiddenDefault($locale)
        ) {
            $redirection = FullLocalization::getLocalizedUrl($locale);
            $redirectResponse = new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);

            return $redirectResponse->withCookie(cookie()->forever(config('full-translation.cookie_name', 'locale'), $locale));
        }

        return $next($request);
    }
}
