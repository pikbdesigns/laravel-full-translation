<?php

use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Http\Middleware\SetLocale;

function registerSetLocaleMiddleware(): void
{
    Route::middleware([SetLocale::class])->get('/{locale}/{path?}', function () {
        return response()->json([
            'locale' => app()->getLocale(),
        ]);
    });
}

beforeEach(function () {
    registerSetLocaleMiddleware();
});

it('sets locale from URL segment', function () {
    $response = $this->get('/es/about');
    $response->assertJson(['locale' => 'es']);
});

it('sets locale from session when no URL locale', function () {
    $this->session(['locale' => 'fr']);
    $response = $this->get('/about');
    $response->assertJson(['locale' => 'fr']);
});

it('sets locale from cookie when no URL or session locale', function () {
    $response = $this->call('GET', '/about', [], ['locale' => 'fr']);
    $response->assertJson(['locale' => 'fr']);
});

it('redirects to localized URL when no locale in URL and session has locale', function () {
    $this->session(['locale' => 'es']);
    $response = $this->get('/about');
    $response->assertJson(['locale' => 'es']);
});

it('uses fallback locale when no locale detected', function () {
    $response = $this->get('/about');
    $response->assertJson(['locale' => 'en']);
});

it('does not redirect if locale is already in URL', function () {
    $response = $this->get('/es/about');
    $response->assertStatus(200);
    $response->assertJson(['locale' => 'es']);
});
