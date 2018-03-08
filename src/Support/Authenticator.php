<?php

namespace PragmaRX\Google2FALaravel\Support;

use Illuminate\Http\Request as IlluminateRequest;
use PragmaRX\Google2FALaravel\Events\EmptyOneTimePasswordReceived;
use PragmaRX\Google2FALaravel\Events\LoginFailed;
use PragmaRX\Google2FALaravel\Events\LoginSucceeded;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;
use PragmaRX\Google2FALaravel\Google2FA;

class Authenticator extends Google2FA
{
    use ErrorBag, Input, Response;

    /**
     * The current password.
     *
     * @var
     */
    protected $password;

    /**
     * Authenticator constructor.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(IlluminateRequest $request)
    {
        parent::__construct($request);
    }

    /**
     * Authenticator boot.
     *
     * @param $request
     *
     * @return Google2FA
     */
    public function boot($request)
    {
        parent::boot($request);

        return $this;
    }

    /**
     * Fire login (success or failed).
     *
     * @param $succeeded
     */
    private function fireLoginEvent($succeeded)
    {
        event(
            $succeeded
                ? new LoginSucceeded($this->getUser())
                : new LoginFailed($this->getUser())
        );

        return $succeeded;
    }

    /**
     * Get the OTP from user input.
     *
     * @throws InvalidOneTimePassword
     *
     * @return mixed
     */
    protected function getOneTimePassword()
    {
        if (is_null($password = $this->getInputOneTimePassword()) || empty($password)) {
            event(new EmptyOneTimePasswordReceived());

            if ($this->config('throw_exceptions', true)) {
                throw new InvalidOneTimePassword('One Time Password cannot be empty.');
            }
        }

        return $password;
    }

    /**
     * Check if the current use is authenticated via OTP.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->canPassWithoutCheckingOTP()
            ? true
            : $this->checkOTP();
    }

    /**
     * Check if it is already logged in or passable without checking for an OTP.
     *
     * @return bool
     */
    protected function canPassWithoutCheckingOTP()
    {
        return
            !$this->isEnabled() ||
            $this->noUserIsAuthenticated() ||
            !$this->isActivated() ||
            $this->twoFactorAuthStillValid();
    }

    /**
     * Check if the input OTP is valid.
     *
     * @return bool
     */
    protected function checkOTP()
    {
        if (!$this->inputHasOneTimePassword()) {
            return false;
        }

        if ($isValid = $this->verifyOneTimePassword()) {
            $this->login();
        }

        return $this->fireLoginEvent($isValid);
    }

    /**
     * Verify the OTP.
     *
     * @return mixed
     */
    protected function verifyOneTimePassword()
    {
        return $this->verifyAndStoreOneTimePassword($this->getOneTimePassword());
    }
}
