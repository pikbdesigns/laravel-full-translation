# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.0] - 2026-07-20

### Added

- URL-based locale prefixes (`/en/about`, `/es/about`) via `Route::localized()` macro
- `SetLocale` middleware for automatic locale detection from URL, session, cookie, or `Accept-Language` header
- `LocaleSessionRedirect` middleware to redirect GET requests to session-stored locale
- `LocaleCookieRedirect` middleware to redirect GET requests to cookie-stored locale
- `HideDefaultLocaleInUrl` middleware to strip prefix for default locale
- Rich locale metadata support (native names, scripts, regional codes)
- `Localization` facade with full API (locale management, URL generation, route translations)
- `FullLocalization` facade (separate package variant)
- Language switcher Blade component with customizable ordering
- `export:translations` Artisan command to scan codebase and export JSON translation files
- `inspect:translations` Artisan command to display translation key completion status
- `TranslationScanner` for extracting translatable strings from PHP and Blade files
- `RouteStringTranslator` for translated route slugs
- Helper functions: `localizeUrl()`, `getCurrentLocale()`, `getSupportedLocales()`, `isDefaultLocale()`, `getNonLocalizedUrl()`, `localizedView()`
- Configurable URL ignore patterns and HTTP method exclusions
- `locales_order` config for custom language switcher ordering
- `locale_mapping` config for URL slug to internal code mapping
- `manual-strings.json` support for dynamic phrases
- `hide_default_locale` config with full redirect behavior
- Support for PHP 8.2+
- Support for Laravel 11, 12, and 13
- Integration tests covering all middleware, facades, routes, scanner, and CLI commands
- README with full documentation and table of contents