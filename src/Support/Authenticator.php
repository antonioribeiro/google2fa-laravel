<?php

namespace PragmaRX\Google2FALaravel\Support;

use Carbon\Carbon;
use Google2FA;
use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;
use PragmaRX\Google2FALaravel\Exceptions\InvalidSecretKey;

class Authenticator
{
    use Config, ErrorBag, Response, Session;

    /**
     * The auth instance.
     *
     * @var
     */
    protected $auth;

    /**
     * The request instance.
     *
     * @var
     */
    protected $request;

    /**
     * The current password.
     *
     * @var
     */
    protected $password;

    /**
     * Authenticator constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->setRequest($request);
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
        return $this->setRequest($request);
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
            $this->twoFactorAuthStillValid();
    }

    /**
     * Get the user Google2FA secret.
     *
     * @throws InvalidSecretKey
     *
     * @return mixed
     */
    protected function getGoogle2FASecretKey()
    {
        $secret = $this->getUser()->{$this->config('otp_secret_column')};

        if (is_null($secret) || empty($secret)) {
            throw new InvalidSecretKey('Secret key cannot be empty.');
        }

        return $secret;
    }

    /**
     * Get the previous OTP.
     *
     * @return null|void
     */
    protected function getOldOneTimePassword()
    {
        $oldPassword = $this->config('forbid_old_passwords') === true
            ? $this->sessionGet(Constants::SESSION_OTP_TIMESTAMP)
            : null;

        return $oldPassword;
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

        $this->password = $this->request->input($this->config('otp_input'));

        if (is_null($this->password) || empty($this->password)) {
            throw new InvalidOneTimePassword('One Time Password cannot be empty.');
        }

        return $this->password;
    }

    /**
     * Get the request instance.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the OTP view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    private function getView()
    {
        return view($this->config('view'));
    }

    /**
     * Keep this OTP session alive.
     */
    protected function keepAlive()
    {
        if ($this->config('keep_alive')) {
            $this->updateCurrentAuthTime();
        }
    }

    /**
     * Check if the request input has the OTP.
     *
     * @return mixed
     */
    protected function inputHasOneTimePassword()
    {
        return $this->request->has($this->config('otp_input'));
    }

    /**
     * Get minutes since last activity.
     *
     * @return int
     */
    protected function minutesSinceLastActivity()
    {
        return Carbon::now()->diffInMinutes(
            $this->sessionGet(Constants::SESSION_AUTH_TIME)
        );
    }

    /**
     * Check if no user is authenticated using OTP.
     *
     * @return bool
     */
    protected function noUserIsAuthenticated()
    {
        return is_null($this->getUser());
    }

    /**
     * Check if OTP has expired.
     *
     * @return bool
     */
    protected function passwordExpired()
    {
        if (($minutes = $this->config('lifetime')) == 0 && $this->minutesSinceLastActivity() > $minutes) {
            $this->logout();

            return true;
        }

        $this->keepAlive();

        return false;
    }

    /**
     * Put a var value to the current session.
     *
     * @param $var
     * @param $value
     *
     * @return mixed
     */
    protected function sessionPut($var, $value)
    {
        $this->request->session()->put(
            $this->makeSessionVarName($var),
            $value
        );

        return $value;
    }

    /**
     * Forget a session var.
     *
     * @param null $var
     */
    protected function sessionForget($var = null)
    {
        $this->request->session()->forget(
            $this->makeSessionVarName($var)
        );
    }

    /**
     * Set the request property.
     *
     * @param mixed $request
     *
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set current auth as valid.
     */
    protected function storeAuthPassed()
    {
        $this->sessionPut(Constants::SESSION_AUTH_PASSED, true);

        $this->updateCurrentAuthTime();
    }

    /**
     * Store the old OTP.
     *
     * @param $key
     *
     * @return mixed
     */
    protected function storeOldOneTimePassord($key)
    {
        return $this->sessionPut(Constants::SESSION_OTP_TIMESTAMP, $key);
    }

    /**
     * Verifies, in the current session, if a 2fa check has already passed.
     *
     * @return bool
     */
    protected function twoFactorAuthStillValid()
    {
        return
            (bool) $this->sessionGet(Constants::SESSION_AUTH_PASSED, false) &&
            !$this->passwordExpired();
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

    /**
     * Check if the current use is authenticated via OTP.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return
            $this->canPassWithoutCheckingOTP()
                ? true
                : $this->checkOTP();
    }

    /**
     * Check if the module is enabled.
     *
     * @return mixed
     */
    protected function isEnabled()
    {
        return $this->config('enabled');
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
            $this->storeAuthPassed();
        }

        return $isValid;
    }

    /**
     * OTP logout.
     */
    public function logout()
    {
        $this->sessionForget();
    }

    /**
     * Update the current auth time.
     */
    protected function updateCurrentAuthTime()
    {
        $this->sessionPut(Constants::SESSION_AUTH_TIME, Carbon::now());
    }

    /**
     * Verify the OTP.
     *
     * @return mixed
     */
    protected function verifyGoogle2FA()
    {
        return $this->storeOldOneTimePassord(
            Google2Fa::verifyKey(
                $this->getGoogle2FASecretKey(),
                $this->getOneTimePassword(),
                $this->config('window'),
                null, // $timestamp
                $this->getOldOneTimePassword()
            )
        );
    }
}
