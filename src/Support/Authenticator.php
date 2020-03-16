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
    use ErrorBag;
    use Input;
    use Response;
    use Session;
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
     * Authenticator boot for API usage.
     *
     * @param $request
     *
     * @return Google2FA
     */
    public function bootStateless($request)
    {
        $this->boot($request);

        $this->setStateless();

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
        $password = $this->getInputOneTimePassword();

        if (is_null($password) || empty($password)) {
            event(new EmptyOneTimePasswordReceived());

            if ($this->config('throw_exceptions', true)) {
                throw new InvalidOneTimePassword(config('google2fa.error_messages.cannot_be_empty'));
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
        return $this->canPassWithoutCheckingOTP() || ($this->checkOTP() === Constants::OTP_VALID);
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
     * Check if the input OTP is valid. Returns one of the possible OTP_STATUS codes:
     * 'empty', 'valid' or 'invalid'.
     *
     * @return string
     */
    protected function checkOTP()
    {
        if (!$this->inputHasOneTimePassword() || empty($this->getInputOneTimePassword())) {
            return Constants::OTP_EMPTY;
        }

        $isValid = $this->verifyOneTimePassword();

        if ($isValid) {
            $this->login();
            $this->fireLoginEvent($isValid);

            return Constants::OTP_VALID;
        }

        $this->fireLoginEvent($isValid);

        return Constants::OTP_INVALID;
    }

    /**
     * Verify the OTP.
     *
     * @throws InvalidOneTimePassword
     *
     * @return mixed
     */
    protected function verifyOneTimePassword()
    {
        return $this->verifyAndStoreOneTimePassword($this->getOneTimePassword());
    }
}
