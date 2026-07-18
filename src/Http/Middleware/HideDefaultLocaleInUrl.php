<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;
use Symfony\Component\HttpFoundation\Response;

class HideDefaultLocaleInUrl extends LocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $params = explode('/', $request->getPathInfo());
        array_shift($params);

        if (count($params) > 0) {
            $locale = $params[0];

            if (FullLocalization::checkLocaleInSupportedLocales($locale)) {
                if (FullLocalization::isHiddenDefault($locale)) {
                    $redirection = FullLocalization::getNonLocalizedUrl();

                    app('session')->reflash();

                    return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
                }
            }
        }

        return $next($request);
    }
}
