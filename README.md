# Laravel Full Translation

URL-based multilingual support for Laravel without rewriting every route.

Add locale prefixes (`/en/about`, `/es/about`) to your Laravel app with minimal setup. The package handles locale detection (URL, session, cookie, browser `Accept-Language`), route registration, URL generation, and translation file management.

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

```php
use Pikbdesigns\FullTranslation\Facades\Localization;

// Basic
Localization::getLocale();                          // 'en'
Localization::setLocale('es');
Localization::getDefaultLocale();                   // 'en'
Localization::isDefaultLocale('en');                // true

// Locales
Localization::getSupportedLocales();                // ['en', 'es', 'fr']
Localization::getSupportedLocalesWithMetadata();    // ['en' => ['name' => 'English', ...], ...]
Localization::getAvailableLocales();                // [['name' => 'English', 'code' => 'en', 'native' => 'English'], ...]
Localization::getLocalesOrder();                    // []

// URLs
Localization::getLocalizedUrl('es');                // https://example.com/es/about
Localization::getLocalizedUrl('es', '/faq', false); // '/es/faq'
Localization::getNonLocalizedUrl('/es/about');       // '/about'

// Route translations
Localization::getRouteTranslations('es');            // require lang/es/routes.php
Localization::getTranslatedRoute('about', 'es');    // 'acerca-de'
Localization::mapLocale('pt-br');                   // 'pt_BR' (if mapped)

// Ignoring
Localization::getUrlsIgnored();                     // ['/nova', '/nova/*']
Localization::getHttpMethodsIgnored();              // ['POST', 'PUT', 'PATCH', 'DELETE']
Localization::isUrlIgnored('/nova/dashboard');      // true
Localization::isHttpMethodIgnored('POST');          // true
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

### Custom ordering

Use `locales_order` to control the display order:

```php
'locales_order' => ['es', 'fr', 'en'],
```

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

Used by `RouteStringTranslator` and accessible via `Localization::getTranslatedRoute()`.

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

## License

MIT