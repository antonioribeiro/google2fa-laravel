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

    protected function getInputOneTimePassword()
    {
        return $this->getRequest()->input($this->config('otp_input'));
    }

    abstract public function getRequest();

    abstract protected function config($string, $children = []);
}
