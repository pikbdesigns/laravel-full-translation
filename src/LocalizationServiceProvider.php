<?php

namespace Pikbdesigns\FullTranslation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Pikbdesigns\FullTranslation\Console\ExportTranslationsCommand;
use Pikbdesigns\FullTranslation\Console\InspectTranslationsCommand;

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
                $defaultLocale = config('full-translation.default_locale', 'en');

                $setLocale = 'Pikbdesigns\FullTranslation\Http\Middleware\SetLocale';
                $hideDefaultLocale = 'Pikbdesigns\FullTranslation\Http\Middleware\HideDefaultLocaleInUrl';

                if ($hideDefault) {
                    Route::group([
                        'prefix' => '',
                        'as' => 'localized.root.',
                        'middleware' => [
                            function ($request, $next) use ($defaultLocale) {
                                app()->setLocale($defaultLocale);

                                return $next($request);
                            },
                        ],
                    ], function () use ($callback) {
                        $callback();
                    });
                } else {
                    $redirectToLocalized = function ($request, $next) use ($defaultLocale) {
                        $manager = app(TranslationManager::class);
                        $locale = $defaultLocale;

                        if (config('full-translation.use_session', true) && session()->has('locale')) {
                            $sessionLocale = session('locale');
                            if ($manager->checkLocaleInSupportedLocales($sessionLocale)) {
                                $locale = $sessionLocale;
                            }
                        }

                        if ($locale === $defaultLocale && config('full-translation.use_cookie', true)) {
                            $cookieLocale = $request->cookie(config('full-translation.cookie_name', 'locale'));
                            if ($cookieLocale && $manager->checkLocaleInSupportedLocales($cookieLocale)) {
                                $locale = $cookieLocale;
                            }
                        }

                        if ($locale === $defaultLocale && config('full-translation.use_accept_language', true)) {
                            $preferred = $request->getPreferredLanguage($manager->getSupportedLocales());
                            if ($preferred) {
                                $locale = $preferred;
                            }
                        }

                        $path = $request->path();
                        $target = $locale.($path ? '/'.$path : '/');

                        $query = $request->getQueryString();

                        return redirect('/'.$target.($query ? '?'.$query : ''));
                    };

                    Route::get('/', function () {
                        $manager = app(TranslationManager::class);
                        $locale = $manager->getDefaultLocale();

                        if (config('full-translation.use_session', true) && session()->has('locale')) {
                            $sessionLocale = session('locale');
                            if ($manager->checkLocaleInSupportedLocales($sessionLocale)) {
                                $locale = $sessionLocale;
                            }
                        }

                        if ($locale === $manager->getDefaultLocale() && config('full-translation.use_cookie', true)) {
                            $cookieLocale = request()->cookie(config('full-translation.cookie_name', 'locale'));
                            if ($cookieLocale && $manager->checkLocaleInSupportedLocales($cookieLocale)) {
                                $locale = $cookieLocale;
                            }
                        }

                        if ($locale === $manager->getDefaultLocale() && config('full-translation.use_accept_language', true)) {
                            $preferred = request()->getPreferredLanguage($manager->getSupportedLocales());
                            if ($preferred) {
                                $locale = $preferred;
                            }
                        }

                        return redirect('/'.$locale.'/');
                    })->name('localized.root')
                        ->middleware($setLocale);

                    Route::group([
                        'prefix' => '',
                        'as' => 'localized.root.',
                        'middleware' => [$redirectToLocalized],
                    ], function () use ($callback) {
                        $callback();
                    });
                }

                foreach ($locales as $locale) {
                    $middleware = [$setLocale];

                    if ($hideDefault && $locale === $defaultLocale) {
                        $middleware[] = $hideDefaultLocale;
                    }

                    Route::group([
                        'prefix' => $locale,
                        'as' => 'localized.'.$locale.'.',
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
