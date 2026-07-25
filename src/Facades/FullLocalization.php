<?php

namespace Pikbdesigns\FullTranslation\Facades;

use Illuminate\Support\Facades\Facade;
use Pikbdesigns\FullTranslation\TranslationManager;

/**
 * @method static string getLocale()
 * @method static void setLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static array getSupportedLocales()
 * @method static array getSupportedLocalesWithMetadata()
 * @method static bool isDefaultLocale(string $locale)
 * @method static array getAvailableLocales()
 * @method static array getLocalesOrder()
 * @method static array getUrlsIgnored()
 * @method static array getHttpMethodsIgnored()
 * @method static bool isUrlIgnored(string $url)
 * @method static bool isHttpMethodIgnored(string $method)
 * @method static string getLocalizedUrl(?string $locale = null, ?string $url = null, bool $full = true)
 * @method static string getNonLocalizedUrl(?string $url = null)
 * @method static array getRouteTranslations(string $locale)
 * @method static ?string getTranslatedRoute(string $route, string $locale)
 * @method static string mapLocale(string $locale)
 *
 * @see TranslationManager
 */
class FullLocalization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TranslationManager::class;
    }
}
