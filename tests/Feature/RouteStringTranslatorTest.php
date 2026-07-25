<?php

use Pikbdesigns\FullTranslation\Routing\RouteStringTranslator;

beforeEach(function () {
    // Create lang/es/routes.php fixture
    $langPath = lang_path('es');
    if (! is_dir($langPath)) {
        mkdir($langPath, 0755, true);
    }
    file_put_contents($langPath.'/routes.php', "<?php\n\nreturn [\n    'about' => 'sobre-nosotros',\n    'contact' => 'contacto',\n];\n");
});

afterEach(function () {
    $file = lang_path('es/routes.php');
    if (file_exists($file)) {
        unlink($file);
    }
});

it('translates a route string using locale-specific routes.php', function () {
    $result = RouteStringTranslator::translateRouteString('about', 'es');
    expect($result)->toBe('sobre-nosotros');
});

it('returns the original string if no translation exists', function () {
    $result = RouteStringTranslator::translateRouteString('pricing', 'es');
    expect($result)->toBe('pricing');
});

it('returns the original string if locale routes.php does not exist', function () {
    $result = RouteStringTranslator::translateRouteString('about', 'de');
    expect($result)->toBe('about');
});
