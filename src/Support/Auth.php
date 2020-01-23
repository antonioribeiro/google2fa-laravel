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
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    protected function getAuth()
    {
        if (is_null($this->auth)) {
            $this->auth = app($this->config('auth'));
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
