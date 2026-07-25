<?php

namespace Pikbdesigns\FullTranslation\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RootRedirect extends LocalizationMiddlewareBase
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $this->detectLocale($request);

        return redirect('/'.$locale.'/');
    }
}