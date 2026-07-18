<?php

namespace Pikbdesigns\FullTranslation\Console;

use Illuminate\Console\Command;

class InspectTranslationsCommand extends Command
{
    protected $signature = 'inspect:translations';

    protected $description = 'Show a summary of all translation keys and their completion status across locales';

    public function handle(): int
    {
        $locales = config('full-translation.supported_locales', ['en']);
        $defaultLocale = config('full-translation.default_locale', 'en');

        // Convert rich metadata to codes if needed
        if (! empty($locales) && is_array(reset($locales))) {
            $locales = array_keys($locales);
        }

        $defaultFile = lang_path($defaultLocale.'.json');
        if (! file_exists($defaultFile)) {
            $this->error("No translation file found for default locale: {$defaultLocale}");

            return self::FAILURE;
        }

        $defaultTranslations = json_decode(file_get_contents($defaultFile), true) ?? [];

        if (empty($defaultTranslations)) {
            $this->warn('No translation keys found.');

            return self::SUCCESS;
        }

        $this->info('Translation Keys Summary');
        $this->line(str_repeat('-', 80));

        $headers = ['Key'];
        foreach ($locales as $locale) {
            $headers[] = strtoupper($locale);
        }
        $headers[] = 'Status';

        $rows = [];
        $completed = 0;
        $total = 0;

        foreach ($defaultTranslations as $key => $value) {
            $row = [$key];
            $allTranslated = true;

            foreach ($locales as $locale) {
                $file = lang_path($locale.'.json');
                $translations = file_exists($file)
                    ? json_decode(file_get_contents($file), true) ?? []
                    : [];

                $translated = $translations[$key] ?? '';
                $row[] = $translated ?: '—';

                if ($locale !== $defaultLocale && ($translated === '' || $translated === $value)) {
                    $allTranslated = false;
                }
            }

            $total++;
            if ($allTranslated) {
                $completed++;
                $row[] = '✓';
            } else {
                $row[] = '✗';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        $this->info("Total keys: {$total}");
        $this->info("Fully translated: {$completed}");
        $this->info('Pending: '.($total - $completed));

        return self::SUCCESS;
    }
}
