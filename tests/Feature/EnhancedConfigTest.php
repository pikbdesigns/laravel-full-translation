<?php

use Pikbdesigns\FullTranslation\Facades\FullLocalization;
use Pikbdesigns\FullTranslation\TranslationManager;

it('returns locale codes from rich metadata config', function () {
    config(['full-translation.supported_locales' => [
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
        'es' => ['name' => 'Spanish', 'script' => 'Latn', 'native' => 'español', 'regional' => 'es_ES'],
    ]]);

    $locales = FullLocalization::getSupportedLocales();
    expect($locales)->toBe(['en', 'es']);
});

it('returns rich metadata when available', function () {
    config(['full-translation.supported_locales' => [
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    ]]);

    $metadata = FullLocalization::getSupportedLocalesWithMetadata();
    expect($metadata)->toHaveKey('en');
    expect($metadata['en']['script'])->toBe('Latn');
    expect($metadata['en']['regional'])->toBe('en_GB');
});

it('respects locales_order in getAvailableLocales', function () {
    config(['full-translation.supported_locales' => [
        'en' => ['name' => 'English', 'native' => 'English'],
        'es' => ['name' => 'Spanish', 'native' => 'español'],
        'fr' => ['name' => 'French', 'native' => 'Français'],
    ]]);
    config(['full-translation.locales_order' => ['fr', 'en', 'es']]);

    $available = FullLocalization::getAvailableLocales();
    expect($available[0]['code'])->toBe('fr');
    expect($available[1]['code'])->toBe('en');
    expect($available[2]['code'])->toBe('es');
});

it('checks if URL is ignored', function () {
    config(['full-translation.urls_ignored' => ['/nova', '/nova/*', '/api/*']]);

    expect(FullLocalization::isUrlIgnored('/nova'))->toBeTrue();
    expect(FullLocalization::isUrlIgnored('/nova/dashboard'))->toBeTrue();
    expect(FullLocalization::isUrlIgnored('/api/users'))->toBeTrue();
    expect(FullLocalization::isUrlIgnored('/about'))->toBeFalse();
});

it('checks if HTTP method is ignored', function () {
    config(['full-translation.http_methods_ignored' => ['POST', 'PUT', 'PATCH', 'DELETE']]);

    expect(FullLocalization::isHttpMethodIgnored('POST'))->toBeTrue();
    expect(FullLocalization::isHttpMethodIgnored('PUT'))->toBeTrue();
    expect(FullLocalization::isHttpMethodIgnored('GET'))->toBeFalse();
    expect(FullLocalization::isHttpMethodIgnored('DELETE'))->toBeTrue();
});

it('handles flat array config for getSupportedLocalesWithMetadata', function () {
    config(['full-translation.supported_locales' => ['en', 'es']]);

    $metadata = FullLocalization::getSupportedLocalesWithMetadata();
    expect($metadata)->toHaveKeys(['en', 'es']);
    expect($metadata['en']['name'])->not->toBeEmpty();
});

it('returns empty URLs ignored by default', function () {
    config(['full-translation.urls_ignored' => []]);

    expect(FullLocalization::getUrlsIgnored())->toBe([]);
});

it('returns default HTTP methods ignored', function () {
    expect(FullLocalization::getHttpMethodsIgnored())->toBe(['POST', 'PUT', 'PATCH', 'DELETE']);
});
