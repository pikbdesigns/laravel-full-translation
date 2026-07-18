<?php

use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Http\Middleware\LocaleSessionRedirect;

function registerSessionRedirectMiddleware(): void
{
    $middleware = ['web', LocaleSessionRedirect::class];
    Route::middleware($middleware)->get('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });
    Route::middleware($middleware)->post('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });
}

beforeEach(function () {
    registerSessionRedirectMiddleware();
    config(['full-translation.supported_locales' => ['en', 'es', 'fr']]);
    config(['full-translation.default_locale' => 'en']);
});

it('does not redirect if locale segment is present in URL', function () {
    $this->session(['locale' => 'fr']);
    $response = $this->get('/es/about');
    $response->assertStatus(200);
});

it('redirects to session locale if no locale segment in URL', function () {
    $this->session(['locale' => 'es']);
    $response = $this->get('/about');
    $response->assertRedirect('/es/about');
});

it('does not redirect on non-GET requests', function () {
    $this->session(['locale' => 'es']);
    $response = $this->post('/about');
    $response->assertStatus(200);
});
