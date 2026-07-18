<?php

namespace Pikbdesigns\FullTranslation\Console;

use Illuminate\Console\Command;
use Pikbdesigns\FullTranslation\Scanning\TranslationScanner;

class ExportTranslationsCommand extends Command
{
    protected $signature = 'export:translations {locales? : Comma-separated list of locales to export (e.g. en,fr,es)}';

    protected $description = 'Scan the codebase for translatable strings and export them to JSON files';

    public function handle(TranslationScanner $scanner): int
    {
        $strings = $scanner->scan();
        $defaultLocale = config('full-translation.default_locale', 'en');
        $supportedLocales = config('full-translation.supported_locales', ['en']);

        // Convert rich metadata to codes if needed
        if (! empty($supportedLocales) && is_array(reset($supportedLocales))) {
            $supportedLocales = array_keys($supportedLocales);
        }

        // Filter by specified locales if argument provided
        $argument = $this->argument('locales');
        if ($argument) {
            $specified = array_map('trim', explode(',', $argument));
            $supportedLocales = array_intersect($supportedLocales, $specified);

            if (empty($supportedLocales)) {
                $this->error('No matching locales found for: '.$argument);

                return self::FAILURE;
            }
        }

        if (empty($strings)) {
            $this->warn('No translatable strings found.');

            return self::SUCCESS;
        }

        // Load default locale translations for fallback values
        $defaultTranslations = $this->loadDefaultTranslations($defaultLocale);

        $this->info('Found '.count($strings).' translatable string(s).');

        foreach ($supportedLocales as $locale) {
            $this->exportLocale($locale, $strings, $defaultLocale, $defaultTranslations);
        }

        $this->info('Export complete.');

        return self::SUCCESS;
    }

    protected function loadDefaultTranslations(string $defaultLocale): array
    {
        $defaultFile = lang_path($defaultLocale.'.json');

        if (file_exists($defaultFile)) {
            return json_decode(file_get_contents($defaultFile), true) ?? [];
        }

        return [];
    }

    protected function exportLocale(string $locale, array $strings, string $defaultLocale, array $defaultTranslations): void
    {
        $localePath = lang_path($locale);
        if (! is_dir($localePath)) {
            mkdir($localePath, 0755, true);
        }

        $jsonFile = $localePath.'.json';
        $existing = [];

        if (file_exists($jsonFile)) {
            $existing = json_decode(file_get_contents($jsonFile), true) ?? [];
        }

        $translations = $this->mergeStrings($existing, $strings, $locale, $defaultLocale, $defaultTranslations);

        if (config('full-translation.add_manual_strings', true)) {
            $translations = $this->mergeManualStrings($translations, $locale, $defaultLocale, $defaultTranslations);
        }

        if (config('full-translation.sort_keys', true)) {
            ksort($translations);
        } else {
            $translations = $this->applySortOrder($translations, $locale, $defaultLocale, $defaultTranslations);
        }

        file_put_contents($jsonFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line("  <info>✓</info> Exported to {$locale}.json (".count($translations).' keys)');
    }

    protected function mergeStrings(array $existing, array $strings, string $locale, string $defaultLocale, array $defaultTranslations): array
    {
        $translations = [];

        foreach ($strings as $string) {
            $translations[$string] = $existing[$string] ?? ($defaultTranslations[$string] ?? $string);
        }

        return $translations;
    }

    protected function mergeManualStrings(array $translations, string $locale, string $defaultLocale, array $defaultTranslations): array
    {
        $manualFile = lang_path('manual-strings.json');

        if (! file_exists($manualFile)) {
            return $translations;
        }

        $manual = json_decode(file_get_contents($manualFile), true) ?? [];

        foreach ($manual as $string) {
            if (! isset($translations[$string])) {
                $translations[$string] = $defaultTranslations[$string] ?? $string;
            }
        }

        return $translations;
    }

    protected function applySortOrder(array $translations, string $locale, string $defaultLocale, array $defaultTranslations): array
    {
        $sortOrder = config('full-translation.translated_sort_order', 'alpha');

        if ($sortOrder === 'top') {
            uasort($translations, function ($a, $b) use ($defaultTranslations) {
                return ($b === ($defaultTranslations[$b] ?? $b) ? 1 : 0) - ($a === ($defaultTranslations[$a] ?? $a) ? 1 : 0);
            });
        } elseif ($sortOrder === 'bottom') {
            uasort($translations, function ($a, $b) use ($defaultTranslations) {
                return ($a === ($defaultTranslations[$a] ?? $a) ? 1 : 0) - ($b === ($defaultTranslations[$b] ?? $b) ? 1 : 0);
            });
        } else {
            ksort($translations);
        }

        return $translations;
    }
}
