<?php

beforeEach(function () {
    $viewsPath = resource_path('views/test-views');
    $localizedPath = $viewsPath.'/welcome';

    if (! is_dir($viewsPath)) {
        mkdir($viewsPath, 0755, true);
    }
    if (! is_dir($localizedPath)) {
        mkdir($localizedPath, 0755, true);
    }

    file_put_contents($viewsPath.'/welcome.blade.php', '<h1>Welcome</h1>');
    file_put_contents($localizedPath.'/es.blade.php', '<h1>Bienvenido</h1>');
    file_put_contents($localizedPath.'/fr.blade.php', '<h1>Bienvenue</h1>');
});

afterEach(function () {
    $localizedPath = resource_path('views/test-views/welcome');
    if (is_dir($localizedPath)) {
        array_map('unlink', glob($localizedPath.'/*.blade.php'));
        rmdir($localizedPath);
    }
    $viewsPath = resource_path('views/test-views');
    if (is_dir($viewsPath)) {
        array_map('unlink', glob($viewsPath.'/*.blade.php'));
        rmdir($viewsPath);
    }
});

it('returns the locale-specific view when it exists', function () {
    app()->setLocale('es');
    $view = localizedView('test-views.welcome');
    expect($view->render())->toContain('Bienvenido');
});

it('falls back to the default view when locale view does not exist', function () {
    app()->setLocale('de');
    $view = localizedView('test-views.welcome');
    expect($view->render())->toContain('Welcome');
});

it('passes data to the view', function () {
    app()->setLocale('es');
    $view = localizedView('test-views.welcome', ['name' => 'Mundo']);
    expect($view->render())->toContain('Bienvenido');
});
