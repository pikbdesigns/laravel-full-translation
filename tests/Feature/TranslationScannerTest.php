<?php

use Pikbdesigns\FullTranslation\Scanning\TranslationScanner;

beforeEach(function () {
    config(['full-translation.scan_paths' => [app_path()]]);
    config(['full-translation.scan_helpers' => ['__', 'trans']]);
});

it('scans for translatable strings', function () {
    $scanner = app(TranslationScanner::class);
    $strings = $scanner->scan();
    expect($strings)->toBeArray();
});

it('extracts __() calls', function () {
    $testDir = app_path('test-scan');
    if (! is_dir($testDir)) {
        mkdir($testDir, 0755, true);
    }
    $testFile = $testDir.'/test.php';
    file_put_contents($testFile, "<?php\n__('Hello World');\n__('Goodbye');\n");

    config(['full-translation.scan_paths' => [app_path('test-scan')]]);

    $scanner = new TranslationScanner;
    $strings = $scanner->scan();

    expect($strings)->toContain('Hello World');
    expect($strings)->toContain('Goodbye');

    @unlink($testFile);
    @rmdir($testDir);
});
