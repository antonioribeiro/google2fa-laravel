<?php

namespace PragmaRX\Google2FALaravel\Support;

trait Request
{
    /**
     * The request instance.
     *
     * @var
     */
    protected $request;

    /**
     * Get the request property.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
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
}
