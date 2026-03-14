<?php

namespace PragmaRX\Google2FALaravel;

use Closure;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class MiddlewareStateless
{
    public function handle($request, Closure $next)
    {
        $authenticator = app(Authenticator::class)->bootStateless($request);

        if ($authenticator->isAuthenticated()) {
            $response = $next($request);

            return $this->attachQueuedCookies($request, $response);
        }

        $response = $authenticator->makeRequestOneTimePasswordResponse();

        return $this->attachQueuedCookies($request, $response);
    }

    /**
     * Attach any queued cookies to the response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function attachQueuedCookies($request, $response)
    {
        if ($cookie = $request->attributes->get('google2fa_cookie')) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
