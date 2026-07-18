<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Illuminate\Http\Request;

class LocalizationMiddlewareBase
{
    /**
     * Determine if the request has a URI that should not be localized.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldIgnore(Request $request): bool
    {
        if (in_array($request->method(), config('full-translation.http_methods_ignored', []))) {
            return true;
        }

        $ignored = config('full-translation.urls_ignored', []);

        foreach ($ignored as $pattern) {
            if ($pattern !== '/') {
                $pattern = trim($pattern, '/');
            }

            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}