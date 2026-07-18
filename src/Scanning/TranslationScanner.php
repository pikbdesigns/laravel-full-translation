<?php

namespace Pikbdesigns\FullTranslation\Scanning;

class TranslationScanner
{
    protected array $helpers;

    protected array $paths;

    protected array $excludedDirectories;

    protected array $filePatterns;

    protected bool $allowNewlines;

    protected array $strings = [];

    public function __construct()
    {
        $this->helpers = config('full-translation.scan_helpers', []);
        $this->paths = config('full-translation.scan_paths', ['app', 'resources/views']);
        $this->excludedDirectories = config('full-translation.excluded_directories', []);
        $this->filePatterns = config('full-translation.file_patterns', ['*.php', '*.blade.php']);
        $this->allowNewlines = config('full-translation.allow_newlines', false);
    }

    public function scan(): array
    {
        $this->strings = [];

        foreach ($this->paths as $path) {
            $fullPath = $this->resolvePath($path);
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath);
            }
        }

        return array_values(array_unique($this->strings));
    }

    protected function resolvePath(string $path): string
    {
        if (is_dir($path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function scanDirectory(string $directory): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($this->isExcluded($file->getPathname())) {
                continue;
            }

            if ($this->matchesFilePattern($file->getFilename())) {
                $this->scanFile($file->getPathname());
            }
        }
    }

    protected function isExcluded(string $path): bool
    {
        foreach ($this->excludedDirectories as $excluded) {
            if (str_contains($path, $excluded)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesFilePattern(string $filename): bool
    {
        foreach ($this->filePatterns as $pattern) {
            $regex = '/^'.str_replace(['\\*', '\\?'], ['.*', '.'], preg_quote($pattern, '/')).'$/';

            if (preg_match($regex, $filename)) {
                return true;
            }
        }

        return false;
    }

    protected function scanFile(string $filePath): void
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return;
        }

        if (! $this->allowNewlines && str_contains($content, "\n")) {
            // Still scan but skip multi-line strings
        }

        foreach ($this->helpers as $helper) {
            $this->extractStringsFromHelper($content, $helper);
        }
    }

    protected function extractStringsFromHelper(string $content, string $helper): void
    {
        if (str_starts_with($helper, '@')) {
            $this->extractFromBladeDirective($content, $helper);
        } else {
            $this->extractFromFunctionCall($content, $helper);
        }
    }

    protected function extractFromFunctionCall(string $content, string $functionName): void
    {
        $pattern = '/(?:(?:\\\\?[\w\\\\]*)::)?(?:'.preg_quote($functionName).')\s*\(\s*[\'"](.+?)[\'"]/s';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $match) {
                if (! $this->allowNewlines && str_contains($match, "\n")) {
                    continue;
                }
                $this->strings[] = $match;
            }
        }
    }

    protected function extractFromBladeDirective(string $content, string $directive): void
    {
        $directiveName = ltrim($directive, '@');
        $pattern = '/@'.preg_quote($directiveName).'\s*\(\s*[\'"](.+?)[\'"]/s';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $match) {
                if (! $this->allowNewlines && str_contains($match, "\n")) {
                    continue;
                }
                $this->strings[] = $match;
            }
        }
    }
}
