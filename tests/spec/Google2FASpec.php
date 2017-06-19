<?php

namespace spec\PragmaRX\Google2FALaravel;

use PhpSpec\ObjectBehavior;
use PragmaRX\Google2FA\Google2FA;

class Google2FALaravelSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Google2FA::class);
    }
}
