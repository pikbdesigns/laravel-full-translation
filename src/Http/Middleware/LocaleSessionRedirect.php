<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;
use Symfony\Component\HttpFoundation\Response;

class LocaleSessionRedirect extends LocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $params = explode('/', $request->path());
        $locale = session('locale', false);

        if (count($params) > 0 && FullLocalization::checkLocaleInSupportedLocales($params[0])) {
            session(['locale' => $params[0]]);

            return $next($request);
        }

        if (empty($locale)) {
            $locale = FullLocalization::getLocale();
        }

        if (
            $locale &&
            FullLocalization::checkLocaleInSupportedLocales($locale) &&
            ! FullLocalization::isHiddenDefault($locale)
        ) {
            app('session')->reflash();
            $redirection = FullLocalization::getLocalizedUrl($locale);

            return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
        }

        return $next($request);
    }
}
