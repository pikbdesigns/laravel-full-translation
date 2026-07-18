<?php

use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Http\Middleware\LocaleCookieRedirect;

function registerCookieRedirectMiddleware(): void
{
    Route::middleware(['web', LocaleCookieRedirect::class])->get('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });
}

beforeEach(function () {
    registerCookieRedirectMiddleware();
    app()->setLocale('en');
    config(['full-translation.supported_locales' => ['en', 'es', 'fr']]);
    config(['full-translation.default_locale' => 'en']);
    config(['full-translation.hide_default_locale' => false]);
    config(['full-translation.cookie_name' => 'locale']);
    config(['full-translation.urls_ignored' => []]);
    config(['full-translation.http_methods_ignored' => ['POST', 'PUT', 'PATCH', 'DELETE']]);
});

it('redirects to cookie locale if cookie differs from current locale', function () {
    $response = $this->withCookie('locale', 'fr')->get('/about');
    $response->assertRedirect('/fr/about');
});

it('redirects to cookie locale even if it matches current locale when URL has no locale', function () {
    app()->setLocale('es');
    $response = $this->withCookie('locale', 'es')->get('/about');
    $response->assertRedirect('/es/about');
});

it('does not redirect if locale segment is present', function () {
    $response = $this->withCookie('locale', 'fr')->get('/es/about');
    $response->assertStatus(200);
});
