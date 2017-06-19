<?php

namespace PragmaRX\Google2FALaravel;

use Closure;

class Middleware
{
    public function handle($request, Closure $next)
    {
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated())
        {
            return $next($request);
        }

        return $authenticator->makeRequestOneTimePasswordResponse();
    }
}
