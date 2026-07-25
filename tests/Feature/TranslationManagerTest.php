<?php

use Pikbdesigns\FullTranslation\TranslationManager;

it('resolves the TranslationManager from the container', function () {
    $manager = app(TranslationManager::class);
    expect($manager)->toBeInstanceOf(TranslationManager::class);
});

it('returns the default locale', function () {
    $manager = app(TranslationManager::class);
    expect($manager->getDefaultLocale())->toBe('en');
});

it('returns supported locales', function () {
    $manager = app(TranslationManager::class);
    expect($manager->getSupportedLocales())->toBe(['en', 'es', 'fr']);
});

it('checks if a locale is the default', function () {
    $manager = app(TranslationManager::class);
    expect($manager->isDefaultLocale('en'))->toBeTrue();
    expect($manager->isDefaultLocale('es'))->toBeFalse();
});

it('returns available locales as array of name/code pairs', function () {
    $manager = app(TranslationManager::class);
    $available = $manager->getAvailableLocales();
    expect($available)->toBeArray();
    expect($available[0])->toHaveKeys(['name', 'code']);
    expect($available[0]['code'])->toBe('en');
});

it('generates a localized url for a given locale', function () {
    $manager = app(TranslationManager::class);
    $url = $manager->getLocalizedUrl('es', '/about', false);
    expect($url)->toBe('/es/about');
});

it('generates a localized url for the current locale', function () {
    app()->setLocale('es');
    $manager = app(TranslationManager::class);
    $url = $manager->getLocalizedUrl(null, '/about', false);
    expect($url)->toBe('/es/about');
});

it('removes locale prefix for the default locale when hide_default_locale is true', function () {
    config(['full-translation.hide_default_locale' => true]);
    $manager = app(TranslationManager::class);
    $url = $manager->getLocalizedUrl('en', '/about', false);
    expect($url)->toBe('/about');
});

it('removes locale prefix from a localized url', function () {
    $manager = app(TranslationManager::class);
    $url = $manager->getNonLocalizedUrl('/es/about');
    expect($url)->toBe('/about');
});

it('returns current locale via getLocale', function () {
    app()->setLocale('fr');
    $manager = app(TranslationManager::class);
    expect($manager->getLocale())->toBe('fr');
});
