<?php

namespace PragmaRX\Google2FALaravel;

use Carbon\Carbon;
use Google2FA;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Http\Response as IlluminateResponse;
use PragmaRX\Google2FALaravel\Exceptions\InvalidSecretKey;
use PragmaRX\Google2FALaravel\Events\OneTimePasswordRequested;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Authenticator
{
    const SESSION_AUTH_PASSED = 'auth_passed';

    const SESSION_AUTH_TIME = 'auth_time';

    const SESSION_OTP_TIMESTAMP = 'otp_timestamp';

    private $auth;

    private $request;

    private $password;

    function __construct(Request $request)
    {
        $this->setRequest($request);
    }

    public function boot($request)
    {
        return $this->setRequest($request);
    }

    private function canPassWithoutCheckingOTP()
    {
        return
            ! $this->isEnabled() ||
            $this->noUserIsAuthenticated() ||
            $this->twoFactorAuthHasPassed() ||
            $this->passwordExpired()
        ;
    }

    private function createErrorBagForMessage($message)
    {
        return new MessageBag([
            'message' => $message
        ]);
    }

    /**
     * @return \Illuminate\Foundation\Application|mixed
     */
    private function getAuth()
    {
        if (is_null($this->auth)) {
            $this->auth = app(config('google2fa.auth'));
        }

        return $this->auth;
    }

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

    private function getGoogle2FASecretKey()
    {
        $secret = $this->getUser()->{config('google2fa.otp_secret_column')};

        if (is_null($secret) || empty($secret)) {
            throw new InvalidSecretKey('Secret key cannot be empty.');
        }

        return $secret;
    }

    /**
     * @return null|void
     */
    private function getOldOneTimePassword()
    {
        $oldPassword = config('google2fa.forbid_old_passwords') === true
            ? $this->get(self::SESSION_OTP_TIMESTAMP)
            : null;

        return $oldPassword;
    }

    private function getOneTimePassword()
    {
        if (! is_null($this->password)) {
            return $this->password;
        }

        $this->password = $this->request->input(config('google2fa.otp_input'));

        if (is_null($this->password) || empty($this->password)) {
            throw new InvalidOneTimePassword('One Time Password cannot be empty.');
        }

        return $this->password;
    }

    /**
     * @param null $name
     * @return mixed
     */
    private function getSessionVar($name = null)
    {
        return
            config('google2fa.session_var') .
            is_null($name) ? '' : '.' . $name
        ;
    }

    private function inputHasOneTimePassword()
    {
        return $this->request->has(config('google2fa.otp_input'));
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
        $view = view(config('google2fa.view'));

        if ($statusCode !== SymfonyResponse::HTTP_OK) {
            $view->withErrors($this->getErrorBagForStatusCode($statusCode));
        }

        return new IlluminateResponse($view, $statusCode);
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

    }

    private function get($var = null)
    {
        $this->request->session()->get(
            config('google2fa.session_var') .
            is_null($var) ? '' : '.' . $var
        );
    }

    private function put($var, $value)
    {
        $this->request->session()->put($var, $value);
    }

    private function forget($var)
    {
        $this->request->session()->forget($var);
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
        $this->put($this->getSessionVar(self::SESSION_AUTH_TIME), Carbon::now());

        $this->put($this->getSessionVar(self::SESSION_AUTH_PASSED), true);
    }

    /**
     * @param $key
     */
    private function storeOldOneTimePassord($key)
    {
        $this->put(self::SESSION_OTP_TIMESTAMP, $key);
    }

    /**
     * Verifies, in the current session, if a 2fa check has already passed.
     *
     * @return bool
     */
    private function twoFactorAuthHasPassed()
    {
        return (bool) $this->get($this->getSessionVar(self::SESSION_AUTH_PASSED), false);
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
        return config('google2fa.enabled');
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
        $this->forget($this->getSessionVar());
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
        $key = Google2Fa::verifyKey(
            $this->getGoogle2FASecretKey(),
            $this->getOneTimePassword(),
            null,
            config('google2fa.window'),
            $this->getOldOneTimePassword()
        );

        $this->storeOldOneTimePassord($key);

        return $key;
    }
}
