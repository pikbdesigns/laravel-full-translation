<?php

namespace Pikbdesigns\FullTranslation\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Pikbdesigns\FullTranslation\LocalizationServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LocalizationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('translations.default_locale', 'en');
        $app['config']->set('translations.supported_locales', ['en', 'es', 'fr']);
        $app['config']->set('translations.locales_order', []);
        $app['config']->set('translations.locale_mapping', []);
        $app['config']->set('translations.hide_default_locale', false);
        $app['config']->set('translations.use_session', true);
        $app['config']->set('translations.use_cookie', true);
        $app['config']->set('translations.cookie_name', 'locale');
        $app['config']->set('translations.cookie_lifetime', 525600);
        $app['config']->set('translations.use_accept_language', true);
        $app['config']->set('translations.urls_ignored', []);
        $app['config']->set('translations.http_methods_ignored', ['POST', 'PUT', 'PATCH', 'DELETE']);
        $app['config']->set('translations.route_prefix', '{locale}');
        $app['config']->set('translations.scan_helpers', ['__', '@lang', 'trans']);
        $app['config']->set('translations.scan_paths', ['app', 'resources/views', 'routes']);
        $app['config']->set('translations.excluded_directories', []);
        $app['config']->set('translations.file_patterns', ['*.php', '*.blade.php']);
        $app['config']->set('translations.allow_newlines', false);
        $app['config']->set('translations.sort_keys', true);
        $app['config']->set('translations.add_manual_strings', true);
        $app['config']->set('translations.exclude_translation_keys', true);
        $app['config']->set('translations.translated_sort_order', 'alpha');
    }
}
