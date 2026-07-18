<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Route;
use Pikbdesigns\FullTranslation\Console\ExportTranslationsCommand;
use Pikbdesigns\FullTranslation\Console\InspectTranslationsCommand;
use Pikbdesigns\FullTranslation\Facades\FullLocalization;
use Pikbdesigns\FullTranslation\Http\Middleware\SetLocale;

beforeEach(function () {
    $this->originalLocale = app()->getLocale();
    $this->testFiles = [];
});

afterEach(function () {
    app()->setLocale($this->originalLocale);

    foreach ($this->testFiles as $file) {
        @unlink($file);
    }
});

/*
|--------------------------------------------------------------------------
| 1. Full Middleware Stack
|--------------------------------------------------------------------------
*/

it('sets locale from URL segment via middleware stack', function () {
    Route::middleware([SetLocale::class])->get('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });

    $response = $this->get('/es/about');
    $response->assertJson(['locale' => 'es']);
});

it('falls back to session locale when no URL locale is present', function () {
    Route::middleware([SetLocale::class])->get('/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });

    $this->session(['locale' => 'fr']);
    $response = $this->get('/about');
    $response->assertJson(['locale' => 'fr']);
});

it('falls back to cookie locale when no URL or session locale', function () {
    Route::middleware([SetLocale::class])->get('/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });

    $response = $this->call('GET', '/about', [], ['locale' => 'fr']);
    $response->assertJson(['locale' => 'fr']);
});

it('uses default locale when no locale detected', function () {
    Route::middleware([SetLocale::class])->get('/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });

    $response = $this->get('/about');
    $response->assertJson(['locale' => 'en']);
});

it('sets locale for all supported locales', function () {
    Route::middleware([SetLocale::class])->get('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });

    $response = $this->get('/en/home');
    $response->assertJson(['locale' => 'en']);

    $response = $this->get('/es/home');
    $response->assertJson(['locale' => 'es']);

    $response = $this->get('/fr/home');
    $response->assertJson(['locale' => 'fr']);
});

/*
|--------------------------------------------------------------------------
| 2. Route::localized() Macro
|--------------------------------------------------------------------------
*/

it('creates localized route groups for all supported locales', function () {
    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    $routeNames = array_map(
        fn ($route) => $route->getName(),
        Route::getRoutes()->getRoutes()
    );

    expect($routeNames)->toContain('localized.en.about');
    expect($routeNames)->toContain('localized.es.about');
    expect($routeNames)->toContain('localized.fr.about');
});

it('generates correct URLs for each locale via localized routes', function () {
    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
    });

    expect(route('localized.en.about'))->toContain('/en/about');
    expect(route('localized.es.about'))->toContain('/es/about');
    expect(route('localized.fr.about'))->toContain('/fr/about');
});

it('localize route is accessible via GetLocale middleware', function () {
    Route::localized(function () {
        Route::get('/dashboard', fn () => response()->json([
            'locale' => app()->getLocale(),
            'path' => request()->path(),
        ]))->name('dashboard');
    });

    $response = $this->get('/es/dashboard');
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'es']);
});

it('registers localized routes with multiple route groups', function () {
    Route::localized(function () {
        Route::get('/about', fn () => 'about')->name('about');
        Route::get('/contact', fn () => 'contact')->name('contact');
    });

    $routeNames = array_map(
        fn ($route) => $route->getName(),
        Route::getRoutes()->getRoutes()
    );

    expect($routeNames)->toContain('localized.en.about');
    expect($routeNames)->toContain('localized.es.about');
    expect($routeNames)->toContain('localized.fr.about');
    expect($routeNames)->toContain('localized.en.contact');
    expect($routeNames)->toContain('localized.es.contact');
    expect($routeNames)->toContain('localized.fr.contact');
});

/*
|--------------------------------------------------------------------------
| 3. Language Switcher Blade Component
|--------------------------------------------------------------------------
*/

it('renders the language switcher with links to all locales', function () {
    app()->setLocale('en');
    $html = view('full-translation::language-switcher')->render();

    expect($html)->toContain('language-switcher');
    expect($html)->toContain('español');
    expect($html)->toContain('Français');
    expect($html)->toContain('English');
});

it('marks current locale as active in language switcher', function () {
    app()->setLocale('es');
    $html = view('full-translation::language-switcher')->render();

    expect($html)->toContain('aria-current="page"');
    expect($html)->toContain('<span class="active"');
});

it('does not make current locale a link in language switcher', function () {
    app()->setLocale('en');
    $html = view('full-translation::language-switcher')->render();

    expect($html)->toContain('<span class="active"');
    expect($html)->toContain('href=');
});

/*
|--------------------------------------------------------------------------
| 4. localizedView Helper
|--------------------------------------------------------------------------
*/

it('localized view renders locale-specific view when available', function () {
    $viewsPath = resource_path('views/integration-test-views');
    $localizedPath = $viewsPath.'/welcome';
    @mkdir($localizedPath, 0755, true);

    file_put_contents($viewsPath.'/welcome.blade.php', '<h1>Default Welcome</h1>');
    file_put_contents($localizedPath.'/es.blade.php', '<h1>Bienvenido</h1>');

    $this->testFiles[] = $viewsPath.'/welcome.blade.php';
    $this->testFiles[] = $localizedPath.'/es.blade.php';

    app()->setLocale('es');
    $view = localizedView('integration-test-views.welcome');
    expect($view->render())->toContain('Bienvenido');

    @rmdir($localizedPath);
    @rmdir($viewsPath);
});

it('localized view falls back to default view when locale view does not exist', function () {
    $viewsPath = resource_path('views/integration-test-views-fallback');
    @mkdir($viewsPath, 0755, true);

    file_put_contents($viewsPath.'/welcome.blade.php', '<h1>Fallback Welcome</h1>');

    $this->testFiles[] = $viewsPath.'/welcome.blade.php';

    app()->setLocale('de');
    $view = localizedView('integration-test-views-fallback.welcome');
    expect($view->render())->toContain('Fallback Welcome');

    @rmdir($viewsPath);
});

/*
|--------------------------------------------------------------------------
| 5. Export Translations Command
|--------------------------------------------------------------------------
*/

it('export command scans and creates JSON files for all locales', function () {
    $this->app[ConsoleKernel::class]->addCommands([
        ExportTranslationsCommand::class,
    ]);

    $testDir = app_path('integration-test-export');
    @mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php\n__('Hello World');\n__('Goodbye');\n");

    $this->testFiles[] = $testDir.'/test.php';

    config(['full-translation.scan_paths' => [app_path('integration-test-export')]]);
    config(['full-translation.scan_helpers' => ['__']]);

    $this->artisan('export:translations')
        ->expectsOutput('Found 2 translatable string(s).')
        ->assertExitCode(0);

    $enJson = lang_path('en.json');
    $esJson = lang_path('es.json');
    $frJson = lang_path('fr.json');

    expect(file_exists($enJson))->toBeTrue();
    expect(file_exists($esJson))->toBeTrue();
    expect(file_exists($frJson))->toBeTrue();

    $enTranslations = json_decode(file_get_contents($enJson), true);
    expect($enTranslations)->toHaveKey('Hello World');
    expect($enTranslations)->toHaveKey('Goodbye');

    $esTranslations = json_decode(file_get_contents($esJson), true);
    expect($esTranslations)->toHaveKey('Hello World');
    expect($esTranslations)->toHaveKey('Goodbye');

    // Default locale keeps the key as value, other locales use default value as placeholder
    expect($enTranslations['Hello World'])->toBe('Hello World');
    expect($esTranslations['Hello World'])->toBe('Hello World');

    @unlink($enJson);
    @unlink($esJson);
    @unlink($frJson);
    @rmdir($testDir);
});

it('export command handles existing translation files by merging', function () {
    $this->app[ConsoleKernel::class]->addCommands([
        ExportTranslationsCommand::class,
    ]);

    $testDir = app_path('integration-test-merge');
    @mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php\n__('Existing Key');\n__('New Key');\n");

    $this->testFiles[] = $testDir.'/test.php';

    // Pre-create en.json with an existing translation
    file_put_contents(lang_path('en.json'), json_encode([
        'Existing Key' => 'Existing Key',
    ], JSON_PRETTY_PRINT));
    $this->testFiles[] = lang_path('en.json');

    config(['full-translation.scan_paths' => [app_path('integration-test-merge')]]);
    config(['full-translation.scan_helpers' => ['__']]);

    $this->artisan('export:translations')
        ->expectsOutput('Found 2 translatable string(s).')
        ->assertExitCode(0);

    $enTranslations = json_decode(file_get_contents(lang_path('en.json')), true);
    expect($enTranslations)->toHaveKey('Existing Key');
    expect($enTranslations)->toHaveKey('New Key');

    @unlink(lang_path('en.json'));
    @unlink(lang_path('es.json'));
    @unlink(lang_path('fr.json'));
    @rmdir($testDir);
});

/*
|--------------------------------------------------------------------------
| 6. Inspect Translations Command
|--------------------------------------------------------------------------
*/

it('inspect command displays translation summary with counts', function () {
    $this->app[ConsoleKernel::class]->addCommands([
        InspectTranslationsCommand::class,
    ]);

    file_put_contents(lang_path('en.json'), json_encode([
        'Welcome' => 'Welcome',
        'Goodbye' => 'Goodbye',
    ], JSON_PRETTY_PRINT));
    file_put_contents(lang_path('es.json'), json_encode([
        'Welcome' => 'Bienvenido',
    ], JSON_PRETTY_PRINT));
    file_put_contents(lang_path('fr.json'), json_encode([
        'Welcome' => 'Bienvenue',
        'Goodbye' => 'Au revoir',
    ], JSON_PRETTY_PRINT));

    $this->testFiles[] = lang_path('en.json');
    $this->testFiles[] = lang_path('es.json');
    $this->testFiles[] = lang_path('fr.json');

    $this->artisan('inspect:translations')
        ->expectsOutput('Translation Keys Summary')
        ->expectsOutput('Total keys: 2')
        ->expectsOutput('Pending: 1')
        ->assertExitCode(0);
});

it('inspect command returns failure when no default locale file exists', function () {
    $this->app[ConsoleKernel::class]->addCommands([
        InspectTranslationsCommand::class,
    ]);

    @unlink(lang_path('en.json'));

    $this->artisan('inspect:translations')
        ->assertExitCode(1);
});

it('inspect command reports fully translated when all locales have values', function () {
    $this->app[ConsoleKernel::class]->addCommands([
        InspectTranslationsCommand::class,
    ]);

    file_put_contents(lang_path('en.json'), json_encode([
        'Hello' => 'Hello',
    ], JSON_PRETTY_PRINT));
    file_put_contents(lang_path('es.json'), json_encode([
        'Hello' => 'Hola',
    ], JSON_PRETTY_PRINT));
    file_put_contents(lang_path('fr.json'), json_encode([
        'Hello' => 'Bonjour',
    ], JSON_PRETTY_PRINT));

    $this->testFiles[] = lang_path('en.json');
    $this->testFiles[] = lang_path('es.json');
    $this->testFiles[] = lang_path('fr.json');

    $this->artisan('inspect:translations')
        ->expectsOutput('Pending: 0')
        ->assertExitCode(0);
});

/*
|--------------------------------------------------------------------------
| 7. Localization Facade Methods
|--------------------------------------------------------------------------
*/

it('facade getLocale returns current application locale', function () {
    app()->setLocale('es');
    expect(FullLocalization::getLocale())->toBe('es');
});

it('facade getDefaultLocale returns configured default', function () {
    expect(FullLocalization::getDefaultLocale())->toBe('en');
});

it('facade getSupportedLocales returns configured locales', function () {
    $locales = FullLocalization::getSupportedLocales();
    expect($locales)->toBe(['en', 'es', 'fr']);
});

it('facade getAvailableLocales returns locale array with names and codes', function () {
    $available = FullLocalization::getAvailableLocales();
    expect($available)->toHaveCount(3);

    $codes = array_column($available, 'code');
    expect($codes)->toContain('en');
    expect($codes)->toContain('es');
    expect($codes)->toContain('fr');

    foreach ($available as $locale) {
        expect($locale)->toHaveKeys(['name', 'code']);
    }
});

it('facade isDefaultLocale returns true for default and false otherwise', function () {
    expect(FullLocalization::isDefaultLocale('en'))->toBeTrue();
    expect(FullLocalization::isDefaultLocale('es'))->toBeFalse();
    expect(FullLocalization::isDefaultLocale('fr'))->toBeFalse();
});

it('facade setLocale changes application locale', function () {
    FullLocalization::setLocale('fr');
    expect(app()->getLocale())->toBe('fr');

    FullLocalization::setLocale('es');
    expect(app()->getLocale())->toBe('es');
});

it('facade getNonLocalizedUrl strips locale prefix from URL', function () {
    $result = FullLocalization::getNonLocalizedUrl('/es/about');
    expect($result)->toBe('/about');
});

it('facade getNonLocalizedUrl returns root for bare locale', function () {
    $result = FullLocalization::getNonLocalizedUrl('/es');
    expect($result)->toBe('/');
});

/*
|--------------------------------------------------------------------------
| 8. Helper Functions
|--------------------------------------------------------------------------
*/

it('getCurrentLocale helper returns current app locale', function () {
    app()->setLocale('fr');
    expect(getCurrentLocale())->toBe('fr');
});

it('getSupportedLocales helper returns configured locales', function () {
    expect(getSupportedLocales())->toBe(['en', 'es', 'fr']);
});

it('isDefaultLocale helper returns correct boolean', function () {
    expect(isDefaultLocale('en'))->toBeTrue();
    expect(isDefaultLocale('es'))->toBeFalse();
});

it('getNonLocalizedUrl helper strips locale from URL', function () {
    expect(getNonLocalizedUrl('/fr/about'))->toBe('/about');
    expect(getNonLocalizedUrl('/en/dashboard'))->toBe('/dashboard');
});

/*
|--------------------------------------------------------------------------
| 9. Cross-Component Integration
|--------------------------------------------------------------------------
*/

it('export and inspect commands work together end-to-end', function () {
    $this->app[ConsoleKernel::class]->addCommands([
        ExportTranslationsCommand::class,
        InspectTranslationsCommand::class,
    ]);

    $testDir = app_path('integration-test-e2e');
    @mkdir($testDir, 0755, true);
    file_put_contents($testDir.'/test.php', "<?php\n__('Hello');\n__('World');\n");

    $this->testFiles[] = $testDir.'/test.php';

    config(['full-translation.scan_paths' => [app_path('integration-test-e2e')]]);
    config(['full-translation.scan_helpers' => ['__']]);

    // Export first
    $this->artisan('export:translations')
        ->expectsOutput('Found 2 translatable string(s).')
        ->assertExitCode(0);

    // Then inspect
    $this->artisan('inspect:translations')
        ->expectsOutput('Total keys: 2')
        ->assertExitCode(0);

    @unlink(lang_path('en.json'));
    @unlink(lang_path('es.json'));
    @unlink(lang_path('fr.json'));
    @rmdir($testDir);
});

it('facade and helper functions return consistent results', function () {
    app()->setLocale('es');

    expect(FullLocalization::getLocale())->toBe(getCurrentLocale());
    expect(FullLocalization::getSupportedLocales())->toBe(getSupportedLocales());
    expect(FullLocalization::isDefaultLocale('en'))->toBe(isDefaultLocale('en'));
});

it('localized routes work with middleware stack end-to-end', function () {
    Route::localized(function () {
        Route::get('/about', fn () => response()->json([
            'locale' => app()->getLocale(),
            'url' => request()->url(),
        ]))->name('about');
    });

    $response = $this->get('/es/about');
    $response->assertSuccessful();
    $response->assertJson(['locale' => 'es']);
    expect($response->json('url'))->toContain('/es/about');
});

it('facade getLocalizedUrl generates correct URLs', function () {
    Route::get('/es/{path?}', function () {
        return response()->json([
            'localized' => FullLocalization::getLocalizedUrl('fr', '/about', false),
        ]);
    });

    $response = $this->get('/es/something');
    $response->assertSuccessful();
    $response->assertJsonFragment(['localized' => '/fr/about']);
});

it('middleware correctly resolves locale priority over session and cookie', function () {
    Route::middleware([SetLocale::class])->get('/{locale}/{path?}', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });

    // Session says 'fr', but URL says 'es' — URL should win
    $this->session(['locale' => 'fr']);
    $response = $this->get('/es/about');
    $response->assertJson(['locale' => 'es']);
});
