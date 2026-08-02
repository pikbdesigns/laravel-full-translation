<?php

use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Http\Controllers\LocaleController;

it('switches locale and persists to session', function () {
    Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

    $response = $this->from('/en/dashboard')->get('/locale/es');

    $response->assertRedirect('/en/dashboard');
    expect(session('locale'))->toBe('es');
    expect(app()->getLocale())->toBe('es');
});

it('queues a cookie when use_cookie is enabled', function () {
    Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

    $response = $this->get('/locale/fr');

    $response->assertCookie('locale', 'fr', false);
});

it('skips session persistence when use_session is false', function () {
    config(['full-translation.use_session' => false]);

    Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

    $this->get('/locale/es');

    expect(session('locale'))->toBeNull();
    expect(app()->getLocale())->toBe('es');
});

it('aborts for unsupported locale', function () {
    Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

    $response = $this->get('/locale/xx');

    $response->assertNotFound();
});
