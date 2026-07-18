<?php

use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Http\Middleware\HideDefaultLocaleInUrl;

function registerHideDefaultMiddleware(): void
{
    Route::middleware([HideDefaultLocaleInUrl::class])->get('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });
}

beforeEach(function () {
    registerHideDefaultMiddleware();
    config(['full-translation.supported_locales' => ['en', 'es', 'fr']]);
    config(['full-translation.default_locale' => 'en']);
    config(['full-translation.hide_default_locale' => true]);
    config(['full-translation.urls_ignored' => []]);
    config(['full-translation.http_methods_ignored' => ['POST', 'PUT', 'PATCH', 'DELETE']]);
});

it('redirects default locale in URL to non-localized URL', function () {
    $response = $this->get('/en/about');
    $response->assertRedirect('/about');
    $response->assertStatus(302);
});

it('does not redirect non-default locale in URL', function () {
    $response = $this->get('/es/about');
    $response->assertStatus(200);
});

it('does not redirect when no locale segment', function () {
    $response = $this->get('/about');
    $response->assertStatus(200);
});
