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
        if (is_null(config($config = Constants::CONFIG_PACKAGE_NAME))) {
            throw new \Exception("Config ({$config}.php) not found. Have you published it?");
        }

        return config(
            implode('.', array_merge([Constants::CONFIG_PACKAGE_NAME, $string], (array) $children))
        );
    }
}
