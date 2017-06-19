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
    /**
     * Constants
     */
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
            $this->twoFactorAuthStillValid()
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
     * Keep this OTP session alive.
     */
    private function keepAlive()
    {
        if ($this->config('keep_alive')) {
            $this->updateCurrentAuthTime();
        }
    }

    /**
     * Make a session var name for.
     *
     * @param null $name
     * @return mixed
     */
    private function makeSessionVarName($name = null)
    {
        return $this->config('session_var') . (is_null($name) || empty($name)? '' : '.' . $name);
    }

    /**
     * Check if the request input has the OTP.
     *
     * @return mixed
     */
    private function inputHasOneTimePassword()
    {
        return $this->request->has($this->config('otp_input'));
    }

    /**
     * Make a JSON response.
     *
     * @param $statusCode
     * @return JsonResponse
     */
    private function makeJsonResponse($statusCode)
    {
        return new JsonResponse(
            $this->getErrorBagForStatusCode($statusCode),
            $statusCode
        );
    }

    /**
     * Make the status code, to respond accordingly.
     *
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

    /**
     * Make a web response.
     *
     * @param $statusCode
     * @return IlluminateResponse
     */
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
        return Carbon::now()->diffInMinutes(
            $this->sessionGet(self::SESSION_AUTH_TIME)
        );
    }

    /**
     * @return bool
     */
    private function noUserIsAuthenticated()
    {
        return is_null($this->getUser());
    }

    /**
     * Check if OTP has expired.
     *
     * @return bool
     */
    private function passwordExpired()
    {
        if (($minutes = $this->config('lifetime')) == 0 && $this->minutesSinceLastActivity() > $minutes) {
            $this->logout();

            return true;
        }

        $this->keepAlive();

        return false;
    }

    /**
     * Get a session var value.
     *
     * @param null $var
     * @return mixed
     */
    private function sessionGet($var = null)
    {
        return $this->request->session()->get(
            $this->makeSessionVarName($var)
        );
    }

    /**
     * Put a var value to the current session.
     *
     * @param $var
     * @param $value
     * @return mixed
     */
    private function sessionPut($var, $value)
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
    private function sessionForget($var = null)
    {
        $this->request->session()->forget(
            $this->makeSessionVarName($var)
        );
    }

    /**
     * Set the request property.
     *
     * @param mixed $request
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
    private function storeAuthPassed()
    {
        $this->sessionPut(self::SESSION_AUTH_PASSED, true);

        $this->updateCurrentAuthTime();
    }

    /**
     * Store the old OTP.
     *
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
    private function twoFactorAuthStillValid()
    {
        return
            (bool) $this->sessionGet(self::SESSION_AUTH_PASSED, false) &&
            ! $this->passwordExpired()
        ;
    }

    /**
     * Get the current user.
     *
     * @return mixed
     */
    private function getUser()
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
                : $this->checkOTP()
        ;
    }

    /**
     * Check if the module is enabled.
     *
     * @return mixed
     */
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

    /**
     * OTP logout.
     */
    public function logout()
    {
        $this->sessionForget();
    }

    /**
     * Create a response to request the OTP.
     *
     * @return IlluminateResponse|JsonResponse
     */
    public function makeRequestOneTimePasswordResponse()
    {
        event(new OneTimePasswordRequested($this->getUser()));

        return
            $this->request->expectsJson()
                ? $this->makeJsonResponse($this->makeStatusCode())
                : $this->makeWebResponse($this->makeStatusCode())
        ;
    }

    /**
     * Update the current auth time.
     */
    private function updateCurrentAuthTime()
    {
        $this->sessionPut(self::SESSION_AUTH_TIME, Carbon::now());
    }

    /**
     * Verify the OTP.
     *
     * @return mixed
     */
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
