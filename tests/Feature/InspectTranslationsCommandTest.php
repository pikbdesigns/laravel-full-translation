<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Pikbdesigns\FullTranslation\Console\InspectTranslationsCommand;

beforeEach(function () {
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
});

afterEach(function () {
    @unlink(lang_path('en.json'));
    @unlink(lang_path('es.json'));
    @unlink(lang_path('fr.json'));
});

it('displays translation summary', function () {
    $this->artisan('inspect:translations')
        ->expectsOutput('Translation Keys Summary')
        ->assertExitCode(0);
});

it('reports total and pending counts', function () {
    $this->artisan('inspect:translations')
        ->expectsOutput('Total keys: 2')
        ->expectsOutput('Pending: 1')
        ->assertExitCode(0);
});

it('returns failure when no default locale file exists', function () {
    @unlink(lang_path('en.json'));
    $this->artisan('inspect:translations')
        ->assertExitCode(1);
});
