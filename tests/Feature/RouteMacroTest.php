<?php

use Illuminate\Support\Facades\Route;

it('registers the localized route macro', function () {
    expect(Route::getFacadeRoot()->hasMacro('localized'))->toBeTrue();
});

it('creates routes for each supported locale', function () {
    Route::localized(function () {
        Route::get('/about', function () {
            return 'about';
        })->name('about');
    });

    $routes = Route::getRoutes()->getRoutes();
    $routeNames = array_map(fn ($route) => $route->getName(), $routes);

    expect($routeNames)->toContain('localized.en.about');
    expect($routeNames)->toContain('localized.es.about');
    expect($routeNames)->toContain('localized.fr.about');
});

it('generates localized URLs via route helper', function () {
    Route::localized(function () {
        Route::get('/about', function () {
            return 'about';
        })->name('about');
    });

    expect(route('localized.en.about'))->toContain('/en/about');
    expect(route('localized.es.about'))->toContain('/es/about');
    expect(route('localized.fr.about'))->toContain('/fr/about');
});

/*
|--------------------------------------------------------------------------
| Root Route: hide_default_locale = true
|--------------------------------------------------------------------------
*/

it('registers root route when hide_default_locale is true', function () {
    config(['full-translation.hide_default_locale' => true]);

    Route::localized(function () {
        Route::get('/', fn () => 'home')->name('home');
    });

    $routes = Route::getRoutes()->getRoutes();
    $routeNames = array_map(fn ($route) => $route->getName(), $routes);

    expect($routeNames)->toContain('localized.root.home');
});

it('serves content at root when hide_default_locale is true', function () {
    config(['full-translation.hide_default_locale' => true]);

    Route::localized(function () {
        Route::get('/', fn () => response()->json(['locale' => app()->getLocale()]))->name('home');
    });

    $response = $this->get('/');
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'en']);
});

it('redirects default locale prefix to root when hide_default_locale is true', function () {
    config(['full-translation.hide_default_locale' => true]);

    Route::localized(function () {
        Route::get('/', fn () => response()->json(['locale' => app()->getLocale()]))->name('home');
    });

    $response = $this->get('/en/');
    $response->assertRedirect('/');
    $response->assertStatus(302);
});

it('serves non-default locale content when hide_default_locale is true', function () {
    config(['full-translation.hide_default_locale' => true]);

    Route::localized(function () {
        Route::get('/', fn () => response()->json(['locale' => app()->getLocale()]))->name('home');
    });

    $response = $this->get('/es/');
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'es']);
});

/*
|--------------------------------------------------------------------------
| Root Route: hide_default_locale = false
|--------------------------------------------------------------------------
*/

it('registers root redirect route when hide_default_locale is false', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $routes = Route::getRoutes()->getRoutes();
    $routeNames = array_map(fn ($route) => $route->getName(), $routes);

    expect($routeNames)->toContain('localized.root');
});

it('redirects root to default locale prefix when hide_default_locale is false', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $response = $this->get('/');
    $response->assertRedirect('/en/');
    $response->assertStatus(302);
});

it('redirects root to session locale prefix when hide_default_locale is false', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $this->session(['locale' => 'es']);
    $response = $this->get('/');
    $response->assertRedirect('/es/');
});

it('serves content at localized routes when hide_default_locale is false', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $response = $this->get('/en/about');
    $response->assertSuccessful();

    $response = $this->get('/es/about');
    $response->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| Rich Metadata Locales
|--------------------------------------------------------------------------
*/

it('creates routes for each locale with rich metadata config', function () {
    config(['full-translation.supported_locales' => [
        'en' => ['name' => 'English', 'native' => 'English'],
        'es' => ['name' => 'Spanish', 'native' => 'español'],
        'fr' => ['name' => 'French', 'native' => 'français'],
    ]]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $routes = Route::getRoutes()->getRoutes();
    $routeNames = array_map(fn ($route) => $route->getName(), $routes);

    expect($routeNames)->toContain('localized.en.about');
    expect($routeNames)->toContain('localized.es.about');
    expect($routeNames)->toContain('localized.fr.about');
});

it('serves content at localized routes with rich metadata config', function () {
    config(['full-translation.supported_locales' => [
        'en' => ['name' => 'English', 'native' => 'English'],
        'es' => ['name' => 'Spanish', 'native' => 'español'],
    ]]);

    Route::localized(function () {
        Route::get('/about', fn () => response()->json(['locale' => app()->getLocale()]))->name('about');
    });

    $response = $this->get('/en/about');
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'en']);

    $response = $this->get('/es/about');
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'es']);
});

/*
|--------------------------------------------------------------------------
| Unlocalized URL Redirect: hide_default_locale = false
|--------------------------------------------------------------------------
*/

it('redirects unlocalized /about to /en/about when hide_default_locale is false', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $response = $this->get('/about');
    $response->assertRedirect('/en/about');
    $response->assertStatus(302);
});

it('redirects unlocalized /about to session locale when hide_default_locale is false', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $this->session(['locale' => 'es']);
    $response = $this->get('/about');
    $response->assertRedirect('/es/about');
});

it('serves localized content after redirect from unlocalized URL', function () {
    config(['full-translation.hide_default_locale' => false]);

    Route::localized(function () {
        Route::get('/dashboard', fn () => response()->json([
            'locale' => app()->getLocale(),
        ]))->name('dashboard');
    });

    $response = $this->get('/dashboard');
    $response->assertRedirect('/en/dashboard');

    $response = $this->followRedirects($this->get('/dashboard'));
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'en']);
});
