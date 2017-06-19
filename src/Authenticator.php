<?php

namespace PragmaRX\Google2FALaravel;

use Google2FA;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Http\Response as IlluminateResponse;
use PragmaRX\Google2FALaravel\Exceptions\InvalidSecretKey;
use PragmaRX\Google2FALaravel\Events\OneTimePasswordRequested;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Authenticator
{
    const CONFIG_PACKAGE_NAME = 'google2fa';

    const SESSION_AUTH_PASSED = 'auth_passed';

    const SESSION_AUTH_TIME = 'auth_time';

    const SESSION_OTP_TIMESTAMP = 'otp_timestamp';

    /**
     * The auth instance.
     *
     * @var
     */
    private $auth;

    /**
     * The request instance.
     *
     * @var
     */
    private $request;

    /**
     * The current password.
     *
     * @var
     */
    private $password;

    /**
     * Authenticator constructor.
     *
     * @param Request $request
     */
    function __construct(Request $request)
    {
        $this->setRequest($request);
    }

    /**
     * Authenticator boot.
     *
     * @param $request
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
    private function canPassWithoutCheckingOTP()
    {
        return
            ! $this->isEnabled() ||
            $this->noUserIsAuthenticated() ||
            $this->twoFactorAuthHasPassed() ||
            $this->passwordExpired()
        ;
    }

    /**
     * Get a config value.
     *
     * @param $string
     * @param array $children
     * @return mixed
     * @throws \Exception
     */
    private function config($string, $children = [])
    {
        if (is_null(config(static::CONFIG_PACKAGE_NAME))) {
            throw new \Exception('Config not found');
        }

        return config(
            implode('.', array_merge([static::CONFIG_PACKAGE_NAME, $string], (array) $children))
        );
    }

    /**
     * Create an error bag and store a message on int.
     *
     * @param $message
     * @return MessageBag
     */
    private function createErrorBagForMessage($message)
    {
        return new MessageBag([
            'message' => $message
        ]);
    }

    /**
     * Get or make an auth instance.
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    private function getAuth()
    {
        if (is_null($this->auth)) {
            $this->auth = app($this->config('auth'));
        }

        return $this->auth;
    }

    /**
     * Get a message bag with a message for a particular status code.
     *
     * @param $statusCode
     * @return MessageBag
     */
    private function getErrorBagForStatusCode($statusCode)
    {
        return $this->createErrorBagForMessage(
            trans(
                config(
                    $statusCode == SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
                        ? 'google2fa.error_messages.wrong_otp'
                        : 'unknown error'
                )
            )
        );
    }

    /**
     * Get the user Google2FA secret.
     *
     * @return mixed
     * @throws InvalidSecretKey
     */
    private function getGoogle2FASecretKey()
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
    private function getOldOneTimePassword()
    {
        $oldPassword = $this->config('forbid_old_passwords') === true
            ? $this->sessionGet(self::SESSION_OTP_TIMESTAMP)
            : null;

        return $oldPassword;
    }

    /**
     * Get the OTP from user input.
     *
     * @return mixed
     * @throws InvalidOneTimePassword
     */
    private function getOneTimePassword()
    {
        if (! is_null($this->password)) {
            return $this->password;
        }

        $this->password = $this->request->input($this->config('otp_input'));

        if (is_null($this->password) || empty($this->password)) {
            throw new InvalidOneTimePassword('One Time Password cannot be empty.');
        }

        return $this->password;
    }

    /**
     * @param null $name
     * @return mixed
     */
    private function makeSessionVarName($name = null)
    {
        return $this->config('session_var') . (is_null($name) || empty($name)? '' : '.' . $name);
    }

    private function inputHasOneTimePassword()
    {
        return $this->request->has($this->config('otp_input'));
    }

    private function makeJsonResponse($statusCode)
    {
        return new JsonResponse(
            $this->getErrorBagForStatusCode($statusCode),
            $statusCode
        );
    }

    /**
     * @return int
     */
    private function makeStatusCode()
    {
        return
            $this->inputHasOneTimePassword() && ! $this->checkOTP()
                ? SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
                : SymfonyResponse::HTTP_OK
        ;
    }

    private function makeWebResponse($statusCode)
    {
        $view = view($this->config('view'));

        if ($statusCode !== SymfonyResponse::HTTP_OK) {
            $view->withErrors($this->getErrorBagForStatusCode($statusCode));
        }

        return new IlluminateResponse($view, $statusCode);
    }

    private function minutesSinceLastActivity()
    {
        return 10;
    }

    /**
     * @return bool
     */
    private function noUserIsAuthenticated()
    {
        return is_null($this->getUser());
    }

    private function passwordExpired()
    {
        return
            ($minutes = $this->config('lifetime')) === 0
            ? false
            : $this->minutesSinceLastActivity() > $minutes
        ;
    }

    private function sessionGet($var = null)
    {
        return $this->request->session()->get(
            $this->makeSessionVarName($var)
        );
    }

    private function sessionPut($var, $value)
    {
        $this->request->session()->put(
            $this->makeSessionVarName($var),
            $value
        );

        return $value;
    }

    private function sessionForget($var = null)
    {
        $this->request->session()->forget(
            $this->makeSessionVarName($var)
        );
    }

    /**
     * @param mixed $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    private function storeAuthPassed()
    {
        $this->sessionPut(self::SESSION_AUTH_PASSED, true);

        $this->sessionPut(self::SESSION_AUTH_TIME, Carbon::now());
    }

    /**
     * @param $key
     * @return mixed
     */
    private function storeOldOneTimePassord($key)
    {
        return $this->sessionPut(self::SESSION_OTP_TIMESTAMP, $key);
    }

    /**
     * Verifies, in the current session, if a 2fa check has already passed.
     *
     * @return bool
     */
    private function twoFactorAuthHasPassed()
    {
        return (bool) $this->sessionGet(self::SESSION_AUTH_PASSED, false);
    }

    private function getUser()
    {
        return $this->getAuth()->user();
    }

    public function isAuthenticated()
    {
        return
            $this->canPassWithoutCheckingOTP()
                ? true
                : $this->checkOTP()
        ;
    }

    private function isEnabled()
    {
        return $this->config('enabled');
    }

    /**
     * Check if the input OTP is valid.
     *
     * @return bool
     */
    private function checkOTP()
    {
        if (! $this->inputHasOneTimePassword()) {
            return false;
        }

        if ($isValid = $this->verifyGoogle2FA()) {
            $this->storeAuthPassed();
        }

        return $isValid;
    }

    public function logout()
    {
        $this->sessionForget();
    }

    public function makeRequestOneTimePasswordResponse()
    {
        event(new OneTimePasswordRequested($this->getUser()));

        return
            $this->request->expectsJson()
                ? $this->makeJsonResponse($this->makeStatusCode())
                : $this->makeWebResponse($this->makeStatusCode())
        ;
    }

    private function verifyGoogle2FA()
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
