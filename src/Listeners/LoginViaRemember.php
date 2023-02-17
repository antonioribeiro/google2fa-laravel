<?php

namespace PragmaRX\Google2FALaravel\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class LoginViaRemember
{
    /**
     * Handle the event.
     *
     * @param \Illuminate\Auth\Events\Login $event
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if (Auth::viaRemember()) {
            $this->registerGoogle2fa($event->user);
        }
    }

    /**
     * Force register Google2fa login.
     *
     * @param User $user
     */
    private function registerGoogle2fa(User $user)
    {
        $secret = $user->{Google2FA::config('otp_secret_column')};

        if (!is_null($secret) && !empty($secret)) {
            Google2FA::login();
        }
    }
}
