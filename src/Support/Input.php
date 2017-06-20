<?php

namespace PragmaRX\Google2FALaravel\Support;

trait Input
{
    /**
     * Check if the request input has the OTP.
     *
     * @return mixed
     */
    protected function inputHasOneTimePassword()
    {
        return $this->getRequest()->has($this->config('otp_input'));
    }

    public function input($var)
    {
        return $this->getRequest()->input($var);
    }

    abstract public function getRequest();

    abstract protected function config($string, $children = []);
}
