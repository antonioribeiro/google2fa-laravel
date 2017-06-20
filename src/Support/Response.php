<?php

namespace PragmaRX\Google2FALaravel\Support;

use Illuminate\Http\JsonResponse as IlluminateJsonResponse;
use Illuminate\Http\Response as IlluminateHtmlResponse;
use PragmaRX\Google2FALaravel\Events\OneTimePasswordRequested;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

trait Response
{
    /**
     * Make a JSON response.
     *
     * @param $statusCode
     *
     * @return JsonResponse
     */
    protected function makeJsonResponse($statusCode)
    {
        return new IlluminateJsonResponse(
            $this->getErrorBagForStatusCode($statusCode),
            $statusCode
        );
    }

    /**
     * Make the status code, to respond accordingly.
     *
     * @return int
     */
    protected function makeStatusCode()
    {
        return
            $this->inputHasOneTimePassword() && !$this->checkOTP()
                ? SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
                : SymfonyResponse::HTTP_OK;
    }

    /**
     * Make a web response.
     *
     * @param $statusCode
     *
     * @return \Illuminate\Http\Response
     */
    protected function makeHtmlResponse($statusCode)
    {
        if ($statusCode !== SymfonyResponse::HTTP_OK) {
            $this->getView()->withErrors($this->getErrorBagForStatusCode($statusCode));
        }

        return new IlluminateHtmlResponse($this->getView(), $statusCode);
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
     * Create a response to request the OTP.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function makeRequestOneTimePasswordResponse()
    {
        event(new OneTimePasswordRequested($this->getUser()));

        return
            $this->request->expectsJson()
                ? $this->makeJsonResponse($this->makeStatusCode())
                : $this->makeHtmlResponse($this->makeStatusCode());
    }
}
