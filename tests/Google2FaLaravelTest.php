<?php

namespace PragmaRX\Google2FALaravel\Tests;

use PHPUnit\Framework\TestCase;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class Google2FaLaravelTest extends TestCase
{
    public function testCanInstantiate()
    {
        $this->assertEquals(6, strlen(Google2FA::generateSecretKey()));
    }
}
