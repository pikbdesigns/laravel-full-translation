<?php

use Illuminate\View\View;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;

if (! function_exists('localizeUrl')) {
    function localizeUrl(?string $locale = null, ?string $url = null, bool $full = true): string
    {
        return FullLocalization::getLocalizedUrl($locale, $url, $full);
    }
}

if (! function_exists('getCurrentLocale')) {
    function getCurrentLocale(): string
    {
        return FullLocalization::getLocale();
    }
}

if (! function_exists('getSupportedLocales')) {
    function getSupportedLocales(): array
    {
        return FullLocalization::getSupportedLocales();
    }
}

if (! function_exists('isDefaultLocale')) {
    function isDefaultLocale(?string $locale = null): bool
    {
        return FullLocalization::isDefaultLocale($locale ?? FullLocalization::getLocale());
    }
}

if (! function_exists('getNonLocalizedUrl')) {
    function getNonLocalizedUrl(?string $url = null): string
    {
        return FullLocalization::getNonLocalizedUrl($url);
    }
}

if (! function_exists('localizedView')) {
    function localizedView(string $view, array $data = [], ?string $locale = null): View
    {
        $locale = $locale ?? app()->getLocale();
        $localizedView = $view.'.'.$locale;
        $fallbackView = $view;

        if (view()->exists($localizedView)) {
            return view($localizedView, $data);
        }

        return view($fallbackView, $data);
    }
}
