<?php

namespace PragmaRX\Google2FALaravel;

use Closure;
use PragmaRX\Google2FALaravel\Support\AuthenticatorController;

class Middleware
{
    public function handle($request, Closure $next)
    {
        $authenticator = app(AuthenticatorController::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            return $next($request);
        }

        return $authenticator->makeRequestOneTimePasswordResponse();
    }
}
