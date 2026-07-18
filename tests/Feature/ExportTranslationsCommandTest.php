<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Pikbdesigns\FullTranslation\Console\ExportTranslationsCommand;

beforeEach(function () {
    $this->app[ConsoleKernel::class]->addCommands([
        ExportTranslationsCommand::class,
    ]);

    config(['full-translation.scan_paths' => [app_path()]]);
    config(['full-translation.scan_helpers' => ['__']]);
});

it('exports translations to JSON files', function () {
    $testDir = app_path('test-export');
    if (! is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    file_put_contents($testDir.'/test.php', "<?php\n__('Exported String');\n");

    config(['full-translation.scan_paths' => [app_path('test-export')]]);

    $this->artisan('export:translations')
        ->expectsOutput('Found 1 translatable string(s).')
        ->assertExitCode(0);

    $jsonFile = lang_path('en.json');
    expect(file_exists($jsonFile))->toBeTrue();

    $translations = json_decode(file_get_contents($jsonFile), true);
    expect($translations)->toHaveKey('Exported String');

    // Cleanup
    @unlink($testDir.'/test.php');
    @rmdir($testDir);
    if (file_exists($jsonFile)) {
        @unlink($jsonFile);
    }
});

it('exports translations for specific locales only', function () {
    $testDir = app_path('test-export-locale');
    if (! is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    file_put_contents($testDir.'/test.php', "<?php\n__('Specific Locale String');\n");

    config(['full-translation.scan_paths' => [app_path('test-export-locale')]]);

    $this->artisan('export:translations', ['locales' => 'en'])
        ->expectsOutput('Found 1 translatable string(s).')
        ->assertExitCode(0);

    $enJson = lang_path('en.json');
    expect(file_exists($enJson))->toBeTrue();
    $enTranslations = json_decode(file_get_contents($enJson), true);
    expect($enTranslations)->toHaveKey('Specific Locale String');

    // Cleanup
    @unlink($testDir.'/test.php');
    @rmdir($testDir);
    @unlink($enJson);
    @unlink(lang_path('es.json'));
    @unlink(lang_path('fr.json'));
});
