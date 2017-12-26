<?php

namespace PragmaRX\Google2FALaravel\Support;

use Carbon\Carbon;
use Google2FA;
use Illuminate\Http\Request as IlluminateRequest;
use PragmaRX\Google2FA\Support\Constants as Google2FAConstants;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;
use PragmaRX\Google2FALaravel\Exceptions\InvalidSecretKey;

class AuthenticatorController
{
    use Auth, Config, ErrorBag, Input, Request, Response;

    /**
     * The current password.
     *
     * @var
     */
    protected $password;

    /**
     * The backend.
     *
     * @var
     */
    protected $backend;


    /**
     * Authenticator constructor.
     *
     * @param IlluminateRequest $request
     */
    public function __construct(IlluminateRequest $request)
    {
        $this->boot($request);
    }

    /**
     * Authenticator boot.
     *
     * @param $request
     *
     * @return Authenticator
     */
    public function boot($request)
    {
        $this->backend = app(Authenticator::class)->boot($request);
        $this->setRequest($request);

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
        return
            $this->backend->canPassWithoutCheckingOTP()
                ? true
                : $this->checkOTP();
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

        if ($isValid = $this->verifyGoogle2FA()) {
            $this->backend->storeAuthPassed();
        }

        return $isValid;
    }

    /**
     * Verify the OTP.
     *
     * @return mixed
     */
    protected function verifyGoogle2FA()
    {
        return $this->backend->storeOldOneTimePassord(
            $this->backend->verifyGoogle2FA(
                $this->backend->getGoogle2FASecretKey(),
                $this->getOneTimePassword()
            )
        );
    }
}
