<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnlocalizedRedirect extends LocalizationMiddlewareBase
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $locale = $this->detectLocale($request);

        $path = $request->path();
        $target = $locale.($path ? '/'.$path : '/');
        $query = $request->getQueryString();

        return redirect('/'.$target.($query ? '?'.$query : ''));
    }
}