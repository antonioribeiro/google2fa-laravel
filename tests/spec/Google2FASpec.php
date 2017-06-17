<?php

namespace spec\PragmaRX\Google2FALaravel;

use PhpSpec\ObjectBehavior;
use PragmaRX\Google2FALaravel\Google2FALaravel;

class Google2FALaravelSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('PragmaRX\Google2FALaravel\Google2FALaravel');
    }
}
