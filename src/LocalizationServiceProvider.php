<?php

namespace Pikbdesigns\FullTranslation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Pikbdesigns\FullTranslation\Console\ExportTranslationsCommand;
use Pikbdesigns\FullTranslation\Console\InspectTranslationsCommand;
use Pikbdesigns\FullTranslation\Http\Middleware\HideDefaultLocaleInUrl;
use Pikbdesigns\FullTranslation\Http\Middleware\RootRedirect;
use Pikbdesigns\FullTranslation\Http\Middleware\SetDefaultLocale;
use Pikbdesigns\FullTranslation\Http\Middleware\SetLocale;
use Pikbdesigns\FullTranslation\Http\Middleware\UnlocalizedRedirect;

class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/full-translation.php', 'full-translation'
        );

        $this->app->singleton(TranslationManager::class, function ($app) {
            return new TranslationManager($app);
        });

        $this->registerRouteMacro();
    }

    protected function registerRouteMacro(): void
    {
        if (! method_exists(Route::class, 'localized')) {
            Route::macro('localized', function (callable $callback) {
                $locales = app(TranslationManager::class)->getSupportedLocales();
                $hideDefault = config('full-translation.hide_default_locale', false);
                $localizedUrls = config('full-translation.localized_urls', true);
                $preserveNames = config('full-translation.route_name_strategy', 'localized') === 'original';

                $setLocale = SetLocale::class;
                $hideDefaultLocale = HideDefaultLocaleInUrl::class;
                $setDefaultLocale = SetDefaultLocale::class;
                $unlocalizedRedirect = UnlocalizedRedirect::class;
                $rootRedirect = RootRedirect::class;

                if (! $localizedUrls) {
                    Route::group([
                        'prefix' => '',
                        'as' => $preserveNames ? '' : 'localized.',
                        'middleware' => [$setLocale],
                    ], function () use ($callback) {
                        $callback();
                    });

                    return;
                }

                $namePrefix = $preserveNames ? '' : 'localized.';

                if ($hideDefault) {
                    Route::group([
                        'prefix' => '',
                        'as' => $preserveNames ? '' : 'localized.root.',
                        'middleware' => [$setDefaultLocale],
                    ], function () use ($callback) {
                        $callback();
                    });
                } else {
                    Route::get('/', $rootRedirect)
                        ->name('localized.root')
                        ->middleware($setLocale);

                    Route::group([
                        'prefix' => '',
                        'as' => $preserveNames ? '' : 'localized.root.',
                        'middleware' => [$unlocalizedRedirect],
                    ], function () use ($callback) {
                        $callback();
                    });
                }

                foreach ($locales as $locale) {
                    $middleware = [$setLocale];

                    if ($hideDefault && $locale === config('full-translation.default_locale', 'en')) {
                        $middleware[] = $hideDefaultLocale;
                    }

                    Route::group([
                        'prefix' => $locale,
                        'as' => $preserveNames ? '' : 'localized.'.$locale.'.',
                        'middleware' => $middleware,
                    ], function () use ($callback, $locale) {
                        app()->setLocale($locale);
                        $callback();
                    });
                }
            });
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/full-translation.php' => config_path('full-translation.php'),
        ], 'translations-config');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'full-translation');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/full-translation'),
        ], 'translations-views');

        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportTranslationsCommand::class,
                InspectTranslationsCommand::class,
            ]);
        }
    }
}
