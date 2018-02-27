<?php

namespace PragmaRX\Google2FALaravel\Support;

use PragmaRX\Google2FALaravel\Google2FA;
use Illuminate\Http\Request as IlluminateRequest;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;

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
     * Get the OTP from user input.
     *
     * @throws InvalidOneTimePassword
     *
     * @return mixed
     */
    protected function getOneTimePassword()
    {
        if (!is_null($this->password)) {
            return $this->password;
        }

        $this->password = $this->getInputOneTimePassword();

        if (is_null($this->password) || empty($this->password)) {
            throw new InvalidOneTimePassword('One Time Password cannot be empty.');
        }

        return $this->password;
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

        return $isValid;
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
