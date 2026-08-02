# Laravel Full Translation

URL-based multilingual support for Laravel without rewriting every route.

Add locale prefixes (`/en/about`, `/es/about`) to your Laravel app with minimal setup. The package handles locale detection (URL, session, cookie, browser `Accept-Language`), route registration, URL generation, and translation file management.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Locale Settings](#locale-settings)
  - [Rich Locale Metadata](#rich-locale-metadata)
  - [Detection & Persistence](#detection--persistence)
  - [URL Behavior](#url-behavior)
  - [Scanner Settings](#scanner-settings)
  - [Export Settings](#export-settings)
- [Middleware](#middleware)
  - [Global middleware (bootstrap/app.php)](#global-middleware-bootstrapappphp)
  - [Recommended order](#recommended-order)
  - [Ignoring URLs and methods](#ignoring-urls-and-methods)
- [Route Registration](#route-registration)
  - [Route::localized() macro](#routelocalized-macro)
  - [Non-prefixed mode](#non-prefixed-mode)
  - [Route name strategy](#route-name-strategy)
  - [hide_default_locale behavior](#hide_default_locale-behavior)
  - [Manual approach](#manual-approach)
- [Config Presets](#config-presets)
- [Helper Functions](#helper-functions)
- [Facade](#facade)
- [Language Switcher](#language-switcher)
  - [Locale switching endpoint](#locale-switching-endpoint)
  - [Custom ordering](#custom-ordering)
- [Mixed Stacks (Web + API)](#mixed-stacks-web--api)
- [Translation Export & Inspect Commands](#translation-export--inspect-commands)
  - [Export translations](#export-translations)
  - [Inspect translations](#inspect-translations)
- [Translation File Structure](#translation-file-structure)
  - [JSON files](#json-files)
  - [Route translations](#route-translations)
  - [Manual strings](#manual-strings)
- [Testing](#testing)
- [Changelog](#changelog)
- [License](#license)

## Requirements

- PHP 8.2+
- Laravel 11+

## Installation

```bash
composer require pikbdesigns/laravel-full-translation
```

The service provider and facade are auto-discovered. Publish the config:

```bash
php artisan vendor:publish --tag=translations-config
```

Optionally publish the views (for the language switcher component):

```bash
php artisan vendor:publish --tag=translations-views
```

## Configuration

The published config lives at `config/full-translation.php`. All keys have sensible defaults.

### Locale Settings

| Key | Default | Description |
|-----|---------|-------------|
| `default_locale` | `'en'` | Fallback locale when none is detected |
| `supported_locales` | `['en', 'es', 'fr']` | Locales your app supports. Can be a flat array or associative array with rich metadata |
| `locale_mapping` | `[]` | Maps URL slugs to internal locale codes (e.g., `'pt-br' => 'pt_BR'`) |
| `hide_default_locale` | `false` | When `true`, the default locale has no URL prefix (`/about` vs `/en/about`) |
| `locales_order` | `[]` | Custom order for locales in the language switcher (e.g., `['es', 'fr', 'en']`) |

### Rich Locale Metadata

`supported_locales` can be a simple array of codes or an associative array with metadata:

```php
// Simple (default)
'supported_locales' => ['en', 'es', 'fr'],

// Rich metadata
'supported_locales' => [
    'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    'es' => ['name' => 'Spanish', 'script' => 'Latn', 'native' => 'español', 'regional' => 'es_ES'],
    'fr' => ['name' => 'French', 'script' => 'Latn', 'native' => 'Français', 'regional' => 'fr_FR'],
],
```

The `native` name is used in the language switcher when available. The `regional` key maps to PHP locale codes for `LC_TIME` and `LC_MONETARY`.

### Detection & Persistence

| Key | Default | Description |
|-----|---------|-------------|
| `use_session` | `true` | Store detected locale in the session |
| `use_cookie` | `true` | Store detected locale in a cookie |
| `cookie_name` | `'locale'` | Cookie name for locale persistence |
| `cookie_lifetime` | `525600` | Cookie lifetime in minutes (default: 1 year) |
| `use_accept_language` | `true` | Use browser `Accept-Language` header for detection |

### URL Behavior

| Key | Default | Description |
|-----|---------|-------------|
| `route_prefix` | `'{locale}'` | Placeholder used in route prefixes |
| `localized_urls` | `true` | When `true`, `Route::localized()` prefixes routes with the locale. When `false`, routes are registered without prefixes (see [Non-prefixed mode](#non-prefixed-mode)) |
| `route_name_strategy` | `'localized'` | Route naming: `'localized'` (`localized.en.about`) or `'original'` (keep your names, see [Route name strategy](#route-name-strategy)) |
| `urls_ignored` | `[]` | URL patterns to skip locale processing (e.g., `['/nova', '/nova/*']`) |
| `http_methods_ignored` | `['POST', 'PUT', 'PATCH', 'DELETE']` | HTTP methods that skip locale processing |

### Scanner Settings

| Key | Default | Description |
|-----|---------|-------------|
| `scan_helpers` | `[...]` | Function/directive names the scanner extracts strings from |
| `scan_paths` | `['app', 'resources/views', 'routes']` | Directories to scan for translatable strings |
| `excluded_directories` | `[]` | Directories to exclude from scanning (relative to `scan_paths`) |
| `file_patterns` | `['*.php', '*.blade.php']` | File patterns to scan (supports `*` and `?` wildcards) |
| `allow_newlines` | `false` | Whether strings containing newlines are included in scanning |

### Export Settings

| Key | Default | Description |
|-----|---------|-------------|
| `sort_keys` | `true` | Sort translation keys alphabetically in exported files |
| `translated_sort_order` | `'alpha'` | Sort order when `sort_keys` is `false`: `'alpha'`, `'top'`, or `'bottom'` |
| `add_manual_strings` | `true` | Automatically add strings from `manual-strings.json` to translation files on export |
| `exclude_translation_keys` | `true` | Exclude Laravel PHP translation keys from JSON export if they have translations |

## Middleware

Register these in your `app/Http/Kernel.php` (or `bootstrap/app.php` in Laravel 11+):

| Middleware | Purpose |
|-----------|---------|
| `SetLocale` | Detects locale from URL, session, cookie, or browser and sets `app()->setLocale()` |
| `LocaleSessionRedirect` | Redirects GET requests to the session-stored locale's URL |
| `LocaleCookieRedirect` | Redirects GET requests to the cookie-stored locale's URL |
| `HideDefaultLocaleInUrl` | 302 redirects `/en/about` to `/about` when `en` is the default |

### Global middleware (bootstrap/app.php)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'locale.set' => \Pikbdesigns\FullTranslation\Http\Middleware\SetLocale::class,
        'locale.session' => \Pikbdesigns\FullTranslation\Http\Middleware\LocaleSessionRedirect::class,
        'locale.cookie' => \Pikbdesigns\FullTranslation\Http\Middleware\LocaleCookieRedirect::class,
        'locale.hide' => \Pikbdesigns\FullTranslation\Http\Middleware\HideDefaultLocaleInUrl::class,
    ]);
})
```

### Recommended order

Apply `SetLocale` early (typically in the `web` middleware group), then the redirect middlewares. Use `HideDefaultLocaleInUrl` when `hide_default_locale` is `true`.

### Ignoring URLs and methods

The `SetLocale` middleware respects `urls_ignored` and `http_methods_ignored`:

```php
// Skip locale processing for Nova and API routes
'urls_ignored' => ['/nova', '/nova/*', '/api/*'],

// Skip locale processing for form submissions
'http_methods_ignored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
```

## Route Registration

### Route::localized() macro

The package registers a `Route::localized()` macro that creates route groups for every supported locale:

```php
Route::localized(function () {
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
});
```

This generates named routes like `localized.en.about`, `localized.es.about`, etc. The `SetLocale` middleware is automatically applied to each group.

### Non-prefixed mode

Set `localized_urls` to `false` when your application does **not** want locale prefixes in URLs. This is common for admin panels, dashboards, or apps that serve one locale per user (e.g., an authenticated user's preferred language).

```php
'localized_urls' => false,
```

With this mode:

```php
Route::localized(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

- Routes are registered at `/dashboard` (no locale prefix)
- Only the `SetLocale` middleware is applied; the locale is resolved from the session, cookie, or `Accept-Language` header
- Routes are named `localized.dashboard`
- The redirect middlewares (`LocaleSessionRedirect`, `LocaleCookieRedirect`, `UnlocalizedRedirect`, `RootRedirect`) are not registered, since there is no localized URL to redirect to

Use the [locale switching endpoint](#locale-switching-endpoint) to let users change their locale in this mode.

### Route name strategy

By default, `Route::localized()` names routes `localized.{locale}.{name}` (e.g., `localized.en.about`). Set `route_name_strategy` to `'original'` to keep the names you give routes in the callback:

```php
'route_name_strategy' => 'original',
```

```php
Route::localized(function () {
    Route::get('/about', [PageController::class, 'about'])->name('about');
});
```

This registers the routes under the plain name `about` (plus `localized.en.about`, `localized.es.about`, etc. under the `'localized'` strategy).

> **Caveat:** a Laravel route name maps to exactly one route. When you register the same name across several locale groups, `route('about')` resolves to the **last-registered** locale's URL. Prefer the default `'localized'` strategy for new apps, and use `'original'` only when migrating an existing app and you need `route('name')` calls to keep working.

### hide_default_locale behavior

When `hide_default_locale` is `false` (default):
- All routes are registered with locale prefixes: `/en/about`, `/es/about`, `/fr/about`
- Unlocalized URLs (e.g., `/about`) redirect to the localized version (e.g., `/en/about`)
- The redirect respects session, cookie, and `Accept-Language` header for locale selection
- Root `/` redirects to `/{locale}/`

When `hide_default_locale` is `true`:
- The default locale is served without a prefix: `/about`
- Other locales still use prefixes: `/es/about`, `/fr/about`
- Visiting `/en/about` redirects to `/about` (via `HideDefaultLocaleInUrl` middleware)

### Manual approach

If you prefer explicit control:

```php
Route::prefix('{locale}')
    ->middleware(['locale.set'])
    ->group(function () {
        Route::get('/about', [PageController::class, 'about'])->name('about');
    });
```

## Config Presets

Common configurations are just a few config keys. Pick the preset that matches your app.

### SEO / public site (locale-prefixed URLs)

```php
'localized_urls' => true,
'route_name_strategy' => 'localized',
'hide_default_locale' => false,
'use_session' => true,
'use_cookie' => true,
'use_accept_language' => true,
```

### Admin panel / per-user locale (no URL prefixes)

```php
'localized_urls' => false,
'route_name_strategy' => 'original',
'hide_default_locale' => false,
'use_session' => true,
'use_cookie' => true,
'use_accept_language' => false,
```

### Hybrid (public web localized, API untouched)

Keep `localized_urls => true` and exclude API paths from locale processing:

```php
'localized_urls' => true,
'urls_ignored' => ['/api', '/api/*', '/nova', '/nova/*'],
'http_methods_ignored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
```

See [Mixed Stacks (Web + API)](#mixed-stacks-web--api) for the full pattern.

## Helper Functions

All helpers are globally available (loaded via autoload files):

```php
// Generate a localized URL for the current or given locale
localizeUrl('es');          // https://example.com/es/about
localizeUrl(null, '/faq');  // https://example.com/en/faq

// Get the current application locale
getCurrentLocale(); // 'en'

// Get all supported locales (codes only)
getSupportedLocales(); // ['en', 'es', 'fr']

// Check if a locale is the default
isDefaultLocale('en'); // true
isDefaultLocale();     // checks current locale

// Strip locale prefix from a URL
getNonLocalizedUrl('/es/about'); // '/about'

// Render a locale-specific view with fallback
localizedView('pages.home');        // tries pages.home.en, falls back to pages.home
localizedView('pages.home', [], 'es'); // tries pages.home.es
```

## Facade

The facade is registered as `FullLocalization`:

```php
use Pikbdesigns\FullTranslation\Facades\FullLocalization;

// Basic
FullLocalization::getLocale();                          // 'en'
FullLocalization::setLocale('es');
FullLocalization::getDefaultLocale();                   // 'en'
FullLocalization::isDefaultLocale('en');                // true

// Locales
FullLocalization::getSupportedLocales();                // ['en', 'es', 'fr']
FullLocalization::getSupportedLocalesWithMetadata();    // ['en' => ['name' => 'English', ...], ...]
FullLocalization::getAvailableLocales();                // [['name' => 'English', 'code' => 'en', 'native' => 'English'], ...]
FullLocalization::getLocalesOrder();                    // []

// URLs
FullLocalization::getLocalizedUrl('es');                // https://example.com/es/about
FullLocalization::getLocalizedUrl('es', '/faq', false); // '/es/faq'
FullLocalization::getNonLocalizedUrl('/es/about');       // '/about'

// Route translations
FullLocalization::getRouteTranslations('es');            // require lang/es/routes.php
FullLocalization::getTranslatedRoute('about', 'es');    // 'acerca-de'
FullLocalization::mapLocale('pt-br');                   // 'pt_BR' (if mapped)

// Locale checks
FullLocalization::checkLocaleInSupportedLocales('es');  // true
FullLocalization::isHiddenDefault('en');                // false

// Ignoring
FullLocalization::getUrlsIgnored();                     // ['/nova', '/nova/*']
FullLocalization::getHttpMethodsIgnored();              // ['POST', 'PUT', 'PATCH', 'DELETE']
FullLocalization::isUrlIgnored('/nova/dashboard');      // true
FullLocalization::isHttpMethodIgnored('POST');          // true
```

## Language Switcher

A Blade component is included at `resources/views/language-switcher.blade.php`:

```blade
@include('full-translation::language-switcher')
```

It renders links for each supported locale (except the current one, shown as active text). When using rich locale metadata, it displays native language names (e.g., "Français" instead of "FR").

You can publish and customize it:

```bash
php artisan vendor:publish --tag=translations-views
```

For a fully custom switcher, render the available locales yourself:

```blade
@php
    $locales = \Pikbdesigns\FullTranslation\Facades\FullLocalization::getAvailableLocales();
    $current = \Pikbdesigns\FullTranslation\Facades\FullLocalization::getLocale();
    $requestUrl = request()->getRequestUri();
@endphp

<div class="language-switcher">
    @foreach ($locales as $locale)
        @if ($locale['code'] !== $current)
            <a href="{{ \Pikbdesigns\FullTranslation\Facades\FullLocalization::getLocalizedUrl($locale['code'], $requestUrl, true) }}"
               hreflang="{{ $locale['code'] }}"
               title="{{ $locale['native'] ?? $locale['name'] }}">
                {{ $locale['native'] ?? strtoupper($locale['code']) }}
            </a>
        @else
            <span class="active" aria-current="page">
                {{ $locale['native'] ?? strtoupper($locale['code']) }}
            </span>
        @endif
    @endforeach
</div>
```

Each link points to the current page translated into that locale (via `getLocalizedUrl()`), with the active locale rendered as a `<span>` instead of a link. In [non-prefixed mode](#non-prefixed-mode), swap the `href` for the locale switching endpoint instead:

```blade
<a href="{{ route('locale.switch', $locale['code']) }}">{{ $locale['native'] ?? strtoupper($locale['code']) }}</a>
```

### Locale switching endpoint

The package ships an invokable `LocaleController` that validates the requested locale, sets it as active, persists it to the session and cookie (respecting `use_session` / `use_cookie`), and redirects back.

Register the route (for example in `routes/web.php`):

```php
use Pikbdesigns\FullTranslation\Http\Controllers\LocaleController;

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
```

The controller returns a `404` for locales not listed in `supported_locales`. Wire it into the language switcher:

```blade
@foreach (\Pikbdesigns\FullTranslation\Facades\FullLocalization::getAvailableLocales() as $locale)
    <a href="{{ route('locale.switch', $locale['code']) }}">{{ $locale['native'] ?? strtoupper($locale['code']) }}</a>
@endforeach
```

This endpoint is especially useful in [non-prefixed mode](#non-prefixed-mode), where there are no localized URLs to link to.

### Custom ordering

Use `locales_order` to control the display order:

```php
'locales_order' => ['es', 'fr', 'en'],
```

## Mixed Stacks (Web + API)

Many apps combine localized web routes with API routes that must not be localized. The pattern is simple: keep `localized_urls => true` for the web, and exclude the API paths from locale processing via `urls_ignored`.

### Config

```php
'localized_urls' => true,
'urls_ignored' => ['/api', '/api/*', '/nova', '/nova/*'],
'http_methods_ignored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
```

### Routes

Register API routes outside `Route::localized()` (typically in `routes/api.php`):

```php
// routes/api.php - not localized
Route::prefix('v1')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

```php
// routes/web.php - localized
Route::localized(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

### Middleware ordering (Laravel 11+)

Laravel 11+ registers middleware in `bootstrap/app.php`. Apply `SetLocale` in the global `web` group so locale detection runs before your route middleware. If you registered the redirect middlewares, keep them after `SetLocale`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'locale.set' => \Pikbdesigns\FullTranslation\Http\Middleware\SetLocale::class,
        'locale.session' => \Pikbdesigns\FullTranslation\Http\Middleware\LocaleSessionRedirect::class,
        'locale.cookie' => \Pikbdesigns\FullTranslation\Http\Middleware\LocaleCookieRedirect::class,
        'locale.hide' => \Pikbdesigns\FullTranslation\Http\Middleware\HideDefaultLocaleInUrl::class,
    ]);

    $middleware->web(append: [
        'locale.set',
        'locale.session',
        'locale.cookie',
    ]);
})
```

The `urls_ignored` patterns make `SetLocale` skip the API paths automatically, so API responses are never locale-redirected and always resolve their own locale (e.g., from an `Accept-Language` header or token).

## Translation Export & Inspect Commands

### Export translations

Scans your codebase for translatable strings and writes them to JSON files:

```bash
# Export for all supported locales
php artisan export:translations

# Export for specific locale(s)
php artisan export:translations en
php artisan export:translations en,fr,es
```

Features:
- Reads `scan_helpers` and `scan_paths` from config
- Respects `file_patterns` and `excluded_directories` for fine-grained scanning control
- Creates/updates `{locale}.json` files in `lang/`
- Default locale values are set to the key itself; other locales receive the default locale's value as a placeholder (so there's always something to display)
- Respects `sort_keys` for alphabetical ordering
- When `sort_keys` is `false`, uses `translated_sort_order` (`'alpha'`, `'top'`, or `'bottom'`)
- Merges strings from `manual-strings.json` when `add_manual_strings` is `true`
- Skips strings containing newlines when `allow_newlines` is `false`

### Inspect translations

Shows a summary table of all keys and their completion status. A key is marked as pending (?) if its value in a non-default locale is identical to the default locale's value, meaning it hasn't been translated yet. Missing keys are also treated as pending.

```bash
php artisan inspect:translations
```

Output:

```
Translation Keys Summary
--------------------------------------------------------------------------------
Key               EN              ES              FR              Status
--------------------------------------------------------------------------------
Welcome           Welcome         Bienvenido      Bienvenue       ?
About             About                                       ?
```

## Translation File Structure

The package uses Laravel's JSON translation files:

```
lang/
+-- en.json          # Default locale
+-- es.json          # Spanish
+-- fr.json          # French
+-- {locale}/
    +-- routes.php   # Route string translations
```

### JSON files

Standard Laravel JSON translations. Keys are the original strings, values are translations:

```json
{
    "Welcome": "Bienvenido",
    "About": "Acerca de"
}
```

### Route translations

The `{locale}/routes.php` file maps route strings for translated URLs:

```php
// lang/es/routes.php
return [
    'about' => 'acerca-de',
    'contact' => 'contacto',
];
```

Used by `RouteStringTranslator` and accessible via `FullLocalization::getTranslatedRoute()`.

### Manual strings

For dynamic phrases that can't be scanned (e.g., constructed at runtime), add them to `lang/manual-strings.json`:

```json
[
    "Hello :name",
    "You have :count items"
]
```

These are automatically merged into translation files when `add_manual_strings` is `true`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on recent changes.

## License

MIT