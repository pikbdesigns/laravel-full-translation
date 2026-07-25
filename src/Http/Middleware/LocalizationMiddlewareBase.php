<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Illuminate\Http\Request;
use Pikbdesigns\FullTranslation\TranslationManager;

class LocalizationMiddlewareBase
{
    /**
     * Determine if the request has a URI that should not be localized.
     */
    protected function shouldIgnore(Request $request): bool
    {
        if (in_array($request->method(), config('full-translation.http_methods_ignored', []))) {
            return true;
        }

        $ignored = config('full-translation.urls_ignored', []);

        foreach ($ignored as $pattern) {
            if ($pattern !== '/') {
                $pattern = trim($pattern, '/');
            }

            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect the preferred locale from session, cookie, or Accept-Language header.
     */
    protected function detectLocale(Request $request): string
    {
        /** @var TranslationManager $manager */
        $manager = app(TranslationManager::class);
        $defaultLocale = config('full-translation.default_locale', 'en');
        $locale = $defaultLocale;

        if (config('full-translation.use_session', true) && session()->has('locale')) {
            $sessionLocale = session('locale');
            if ($manager->checkLocaleInSupportedLocales($sessionLocale)) {
                $locale = $sessionLocale;
            }
        }

        if ($locale === $defaultLocale && config('full-translation.use_cookie', true)) {
            $cookieLocale = $request->cookie(config('full-translation.cookie_name', 'locale'));
            if ($cookieLocale && $manager->checkLocaleInSupportedLocales($cookieLocale)) {
                $locale = $cookieLocale;
            }
        }

        if ($locale === $defaultLocale && config('full-translation.use_accept_language', true)) {
            $preferred = $request->getPreferredLanguage($manager->getSupportedLocales());
            if ($preferred) {
                $locale = $preferred;
            }
        }

        return $locale;
    }
}
