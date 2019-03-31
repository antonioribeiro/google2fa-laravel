<?php

namespace PragmaRX\Google2FALaravel\Support;

trait Session
{
    /**
     * Flag to disable the session for API usage.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * Make a session var name for.
     *
     * @param null $name
     *
     * @return mixed
     */
    protected function makeSessionVarName($name = null)
    {
        return $this->config('session_var').(is_null($name) || empty($name) ? '' : '.'.$name);
    }

    /**
     * Get a session var value.
     *
     * @param null $var
     *
     * @return mixed
     */
    public function sessionGet($var = null, $default = null)
    {
        if ($this->stateless) {
            return $default;
        }

        return $this->getRequest()->session()->get(
            $this->makeSessionVarName($var),
            $default
        );
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
        if ($this->stateless) {
            return $value;
        }

        $this->getRequest()->session()->put(
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
        if ($this->stateless) {
            return;
        }

        $this->getRequest()->session()->forget(
            $this->makeSessionVarName($var)
        );
    }

    /**
     * @param mixed $stateless
     *
     * @return Authenticator
     */
    public function setStateless($stateless = true)
    {
        $this->stateless = $stateless;

        return $this;
    }

    abstract protected function config($string, $children = []);

    abstract public function getRequest();
}
