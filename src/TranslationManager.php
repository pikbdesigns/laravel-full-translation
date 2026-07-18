<?php

namespace Pikbdesigns\FullTranslation;

use Illuminate\Contracts\Foundation\Application;

class TranslationManager
{
    public function __construct(
        protected Application $app,
    ) {}

    public function getLocale(): string
    {
        return $this->app->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->app->setLocale($locale);
    }

    public function getDefaultLocale(): string
    {
        return config('full-translation.default_locale', 'en');
    }

    public function getSupportedLocales(): array
    {
        $locales = config('full-translation.supported_locales', ['en']);

        if ($this->isRichLocales($locales)) {
            return array_keys($locales);
        }

        return $locales;
    }

    public function getSupportedLocalesWithMetadata(): array
    {
        $locales = config('full-translation.supported_locales', ['en']);

        if ($this->isRichLocales($locales)) {
            return $locales;
        }

        return array_reduce($locales, function (array $result, string $code) {
            $result[$code] = [
                'name' => locale_get_display_name($code, $code) ?? strtoupper($code),
                'script' => '',
                'native' => locale_get_display_name($code, $code) ?? strtoupper($code),
                'regional' => '',
            ];

            return $result;
        }, []);
    }

    public function isDefaultLocale(string $locale): bool
    {
        return $locale === $this->getDefaultLocale();
    }

    public function isHiddenDefault(string $locale): bool
    {
        return $this->isDefaultLocale($locale) && config('full-translation.hide_default_locale', false);
    }

    public function checkLocaleInSupportedLocales(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales());
    }

    public function getAvailableLocales(): array
    {
        $metadata = $this->getSupportedLocalesWithMetadata();
        $order = $this->getLocalesOrder();

        if (! empty($order)) {
            $ordered = [];
            foreach ($order as $code) {
                if (isset($metadata[$code])) {
                    $ordered[$code] = $metadata[$code];
                }
            }
            foreach ($metadata as $code => $info) {
                if (! isset($ordered[$code])) {
                    $ordered[$code] = $info;
                }
            }
            $metadata = $ordered;
        }

        return array_map(function (string $code, array $info) {
            return [
                'name' => $info['name'] ?? strtoupper($code),
                'code' => $code,
                'native' => $info['native'] ?? $info['name'] ?? strtoupper($code),
                'script' => $info['script'] ?? '',
                'regional' => $info['regional'] ?? '',
            ];
        }, array_keys($metadata), $metadata);
    }

    public function getLocalesOrder(): array
    {
        return config('full-translation.locales_order', []);
    }

    public function getUrlsIgnored(): array
    {
        return config('full-translation.urls_ignored', []);
    }

    public function getHttpMethodsIgnored(): array
    {
        return config('full-translation.http_methods_ignored', ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    public function isUrlIgnored(string $url): bool
    {
        $ignored = $this->getUrlsIgnored();

        foreach ($ignored as $pattern) {
            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                if (str_starts_with($url, $prefix)) {
                    return true;
                }
            } elseif ($url === $pattern) {
                return true;
            }
        }

        return false;
    }

    public function isHttpMethodIgnored(string $method): bool
    {
        return in_array(strtoupper($method), $this->getHttpMethodsIgnored());
    }

    public function getLocalizedUrl(?string $locale = null, ?string $url = null, bool $full = true): string
    {
        $locale ??= $this->getLocale();
        $url ??= request()->getRequestUri();

        $url = $this->getNonLocalizedUrl($url);

        if ($this->isDefaultLocale($locale) && config('full-translation.hide_default_locale', false)) {
            return $full ? url($url) : $url;
        }

        $localized = '/'.$locale.$url;

        return $full ? url($localized) : $localized;
    }

    public function getNonLocalizedUrl(?string $url = null): string
    {
        $url ??= request()->getRequestUri();

        $supported = $this->getSupportedLocales();
        $pattern = '/^\/('.implode('|', array_map('preg_quote', $supported)).')(\/|$|\?)/';

        return preg_replace($pattern, '$2', $url) ?: '/';
    }

    public function getRouteTranslations(string $locale): array
    {
        $file = lang_path($locale.'/routes.php');

        if (! file_exists($file)) {
            return [];
        }

        return require $file;
    }

    public function getTranslatedRoute(string $route, string $locale): ?string
    {
        $translations = $this->getRouteTranslations($locale);

        return $translations[$route] ?? null;
    }

    public function mapLocale(string $locale): string
    {
        $mapping = config('full-translation.locale_mapping', []);

        return $mapping[$locale] ?? $locale;
    }

    protected function isRichLocales(array $locales): bool
    {
        if (empty($locales)) {
            return false;
        }

        $first = reset($locales);

        return is_array($first) && isset($first['name']);
    }
}
