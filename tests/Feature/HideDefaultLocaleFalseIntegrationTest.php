<?php

use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;

beforeEach(function () {
    config([
        'translations.default_locale' => 'en',
        'translations.supported_locales' => [
            'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
            'es' => ['name' => 'Spanish', 'script' => 'Latn', 'native' => 'español', 'regional' => 'es_ES'],
            'fr' => ['name' => 'French', 'script' => 'Latn', 'native' => 'Français', 'regional' => 'fr_FR'],
        ],
        'translations.hide_default_locale' => false,
    ]);

    Route::localized(function () {
        Route::get('/tests', fn () => response()->json([
            'locale' => app()->getLocale(),
            'url' => request()->url(),
            'uri' => request()->getRequestUri(),
        ]))->name('tests');

        Route::get('/about', fn () => response()->json([
            'locale' => app()->getLocale(),
        ]))->name('about');
    });
});

it('serves localized route at /en/tests without redirect', function () {
    $response = $this->get('/en/tests');

    $response->assertSuccessful();
    $response->assertJson(['locale' => 'en']);
});

it('does not redirect /en/tests to /tests', function () {
    $response = $this->get('/en/tests');

    $response->assertSuccessful();
    $this->assertNull($response->headers->get('Location'));
});

it('serves all locale-prefixed routes when hide_default_locale is false', function () {
    $this->get('/en/tests')->assertSuccessful()->assertJson(['locale' => 'en']);
    $this->get('/es/tests')->assertSuccessful()->assertJson(['locale' => 'es']);
    $this->get('/fr/tests')->assertSuccessful()->assertJson(['locale' => 'fr']);
});

it('redirects root to /en/ when hide_default_locale is false', function () {
    $response = $this->get('/');

    $response->assertRedirect('/en/');
});

it('generates correct language-switcher URLs when visiting /en/tests', function () {
    app()->setLocale('en');
    $requestUrl = '/en/tests';

    $esUrl = FullLocalization::getLocalizedUrl('es', $requestUrl, true);
    $frUrl = FullLocalization::getLocalizedUrl('fr', $requestUrl, true);
    $enUrl = FullLocalization::getLocalizedUrl('en', $requestUrl, true);

    expect($esUrl)->toContain('/es/tests');
    expect($frUrl)->toContain('/fr/tests');
    expect($enUrl)->toContain('/en/tests');

    expect($esUrl)->not->toBe(url('/tests'));
    expect($frUrl)->not->toBe(url('/tests'));
    expect($enUrl)->not->toBe(url('/tests'));
});

it('generates correct language-switcher URLs when visiting /es/tests', function () {
    app()->setLocale('es');
    $requestUrl = '/es/tests';

    $enUrl = FullLocalization::getLocalizedUrl('en', $requestUrl, true);
    $frUrl = FullLocalization::getLocalizedUrl('fr', $requestUrl, true);

    expect($enUrl)->toContain('/en/tests');
    expect($frUrl)->toContain('/fr/tests');
});

it('language-switcher component renders correct hrefs for hide_default_locale false', function () {
    app()->setLocale('en');
    $html = view('full-translation::language-switcher')->render();

    expect($html)->toContain('/es');
    expect($html)->toContain('/fr');
    expect($html)->toContain('español');
    expect($html)->toContain('Français');

    expect($html)->not->toContain('href="http://localhost/tests"');
});

it('respects hide_default_locale false with simple array locales', function () {
    config([
        'translations.supported_locales' => ['en', 'es', 'fr'],
        'translations.hide_default_locale' => false,
    ]);

    Route::localized(function () {
        Route::get('/dashboard', fn () => response()->json([
            'locale' => app()->getLocale(),
        ]))->name('dashboard');
    });

    $this->get('/en/dashboard')->assertSuccessful()->assertJson(['locale' => 'en']);
    $this->get('/es/dashboard')->assertSuccessful()->assertJson(['locale' => 'es']);
    $this->get('/fr/dashboard')->assertSuccessful()->assertJson(['locale' => 'fr']);
});
