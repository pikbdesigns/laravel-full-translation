<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;
use Symfony\Component\HttpFoundation\Response;

class SetLocale extends LocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $locale = $this->resolveLocale($request);

        if ($locale === null) {
            $locale = config('full-translation.default_locale', 'en');
        }

        FullLocalization::setLocale($locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): ?string
    {
        $urlLocale = $request->segment(1);
        if ($urlLocale && FullLocalization::checkLocaleInSupportedLocales($urlLocale)) {
            return FullLocalization::mapLocale($urlLocale);
        }

        if (config('full-translation.use_session', true) && session()->has('locale')) {
            $sessionLocale = session('locale');
            if (FullLocalization::checkLocaleInSupportedLocales($sessionLocale)) {
                return $sessionLocale;
            }
        }

        if (config('full-translation.use_cookie', true) && $request->hasCookie(config('full-translation.cookie_name', 'locale'))) {
            $cookieLocale = $request->cookie(config('full-translation.cookie_name', 'locale'));
            if ($cookieLocale && FullLocalization::checkLocaleInSupportedLocales($cookieLocale)) {
                return $cookieLocale;
            }
        }

        if (config('full-translation.use_accept_language', true)) {
            return $request->getPreferredLanguage(FullLocalization::getSupportedLocales());
        }

        return null;
    }

    protected function hasLocaleInUrl(Request $request): bool
    {
        $segment = $request->segment(1);

        return $segment && FullLocalization::checkLocaleInSupportedLocales($segment);
    }
}
