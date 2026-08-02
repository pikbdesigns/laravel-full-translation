<?php

namespace Pikbdesigns\FullTranslation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;

class LocaleController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! FullLocalization::checkLocaleInSupportedLocales($locale)) {
            abort(404);
        }

        FullLocalization::setLocale($locale);

        if (config('full-translation.use_session', true)) {
            session(['locale' => $locale]);
        }

        $redirect = redirect()->back();

        if (config('full-translation.use_cookie', true)) {
            $redirect = $redirect->withCookie(cookie()->forever(config('full-translation.cookie_name', 'locale'), $locale));
        }

        return $redirect;
    }
}
