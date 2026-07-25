<?php

namespace Pikbdesigns\FullTranslation\Routing;

class RouteStringTranslator
{
    public static function translateRouteString(string $routeString, string $locale): string
    {
        $translations = self::getRouteTranslations($locale);

        if (empty($translations)) {
            return $routeString;
        }

        $keys = array_keys($translations);
        $values = array_values($translations);

        $translated = str_replace($keys, $values, $routeString);

        return $translated !== $routeString ? $translated : $routeString;
    }

    protected static function getRouteTranslations(string $locale): array
    {
        $file = lang_path($locale.'/routes.php');

        if (! file_exists($file)) {
            return [];
        }

        return require $file;
    }
}
