<?php

namespace PragmaRX\Google2FALaravel\Support;

trait Auth
{
    /**
     * The auth instance.
     *
     * @var
     */
    protected $auth;

    /**
     * Get or make an auth instance.
     * Supports single or multiple guards defined in 'google2fa.guard'.
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    protected function getAuth()
    {
        if (is_null($this->auth)) {
            $this->auth = app($this->config('auth'));

            $guards = $this->config('guard');

            if (!empty($guards)) {
                if (!is_array($guards)) {
                    $guards = [$guards];
                }

                foreach ($guards as $guard) {
                    if (auth()->guard($guard)->check()) {
                        $this->auth = auth()->guard($guard);
                        break;
                    }
                }
            }
        }

        return $this->auth;
    }

    /**
     * Get the current user.
     *
     * @return mixed
     */
    protected function getUser()
    {
        return $this->getAuth()->user();
    }

    abstract protected function config($string, $children = []);
}
