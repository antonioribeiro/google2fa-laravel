<?php

namespace spec\PragmaRX\Google2FALaravel;

use Illuminate\Http\Request;
use PhpSpec\Laravel\LaravelObjectBehavior;
use PragmaRX\Google2FALaravel\Authenticator;

class AuthenticatorSpec extends LaravelObjectBehavior
{
    function let(Request $request)
    {
        $this->beConstructedWith($request);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Authenticator::class);
    }

    public function it_checks_otp()
    {
        $this->isAuthenticated()->shouldReturn(true);
    }

    public function it_boots()
    {
        $this->boot($request = new Request());

        $this->getRequest()->shouldReturn($request);
    }
}
