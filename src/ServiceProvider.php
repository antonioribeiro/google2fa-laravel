<?php

namespace PragmaRX\Google2FALaravel;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Configure package paths.
     */
    private function configurePaths()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('google2fa.php'),
        ]);
    }

    /**
     * Merge configuration.
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/config.php',
            'google2fa'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pragmarx.google2fa', function ($app) {
            return $app->make(Google2FA::class);
        });
    }

    public function boot()
    {
        $this->configurePaths();

        $this->mergeConfig();
    }
}
