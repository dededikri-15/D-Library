<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('app.supported_locales', ['id', 'en']);
        $locale = $request->session()->get('locale', config('app.locale', 'id'));

        if (! in_array($locale, $supported, true)) {
            $locale = 'id';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
