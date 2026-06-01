<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['en' => 'en', 'pt' => 'pt_BR'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request->header('Accept-Language', '')));

        return $next($request);
    }

    private function resolveLocale(string $header): string
    {
        foreach ($this->parseAcceptLanguage($header) as $tag) {
            $primary = strtolower(explode('-', $tag)[0]);
            if (isset(self::SUPPORTED[$primary])) {
                return self::SUPPORTED[$primary];
            }
        }

        return 'pt_BR';
    }

    /** @return list<string> */
    private function parseAcceptLanguage(string $header): array
    {
        if (trim($header) === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $header));
        $locales = [];

        foreach ($parts as $part) {
            [$tag, $q] = array_pad(explode(';q=', $part, 2), 2, '1.0');
            $locales[trim($tag)] = (float) $q;
        }

        arsort($locales);

        return array_keys($locales);
    }
}
