<?php

namespace PragmaRX\Google2FALaravel\Support;

trait Config
{
    /**
     * Get a config value.
     *
     * @param $string
     * @param array $children
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function config($string, $children = [])
    {
        if (is_null(config(Constants::CONFIG_PACKAGE_NAME))) {
            throw new \Exception('Config not found');
        }

        return config(
            implode('.', array_merge([Constants::CONFIG_PACKAGE_NAME, $string], (array) $children))
        );
    }

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
}
