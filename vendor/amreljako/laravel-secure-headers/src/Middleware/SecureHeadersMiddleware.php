<?php

namespace Amreljako\SecureHeaders\Middleware;

use Closure;

class SecureHeadersMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $headers = config('secure-headers');

        foreach ($headers as $header => $value) {
            if ($value !== null) {
                $response->headers->set($header, $value);
            }
        }

        return $response;
    }
}
