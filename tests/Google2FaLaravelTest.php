<?php

namespace PragmaRX\Google2FALaravel\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Tests\Constants;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use PragmaRX\Google2FALaravel\Tests\Support\User;

class Google2FaLaravelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \PragmaRX\Google2FALaravel\ServiceProvider::class,
            \Illuminate\Auth\AuthServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Google2FA' => \PragmaRX\Google2FALaravel\Facade::class,
            'Auth'      => \Illuminate\Support\Facades\Auth::class,
        ];
    }

    protected function home()
    {
        return $this->call('GET', 'home')->getContent();
    }

    private function loginUser()
    {
        $user = new User();

        $user->username = 'foo';

        $user->google2fa_secret = Constants::SECRET;

        Auth::login($user);
    }

    protected function assertLogin($password = null, $message = 'google2fa passed')
    {
        $this->assertContains(
            $message,
            $this->call('POST', 'login', ['one_time_password' => $password])->getContent()
        );
    }

    public function setUp()
    {
        parent::setup();

        $this->app->make('Illuminate\Contracts\Http\Kernel')->pushMiddleware('Illuminate\Session\Middleware\StartSession');

        \View::addLocation(__DIR__.'/views');

        $this->loginUser();
    }

    protected function getEnvironmentSetUp($app)
    {
        config(['app.debug' => true]);

        $app['router']->get('home', ['as' => 'home', 'uses' => function () {
            return 'we are home';
        }])->middleware(\PragmaRX\Google2FALaravel\Middleware::class);

        $app['router']->post('login', ['as' => 'login.post', 'uses' => function () {
            return 'google2fa passed';
        }])->middleware(\PragmaRX\Google2FALaravel\Middleware::class);

        $app['router']->post('logout', ['as' => 'logout.post', 'uses' => function () {
            Google2FA::logout();
        }]);
    }

    public function getOTP()
    {
        return Google2FA::getCurrentOtp(Auth::user()->google2fa_secret);
    }

    // --------------------------------------------- tests

    public function testCanInstantiate()
    {
        $this->assertEquals(16, strlen(Google2FA::generateSecretKey()));
    }

    public function testIsActivated()
    {
        $this->assertTrue(Google2FA::isActivated());
    }

    public function testVerifyGoogle2FA()
    {
        $this->assertFalse(Google2FA::verifyGoogle2FA(Auth::user()->google2fa_secret, '000000'));
    }

    public function testRedirectToGoogle2FAView()
    {
        $this->assertContains(
            'google2fa view',
            $this->home()
        );
    }

    public function testGoogle2FAPostPasses()
    {
        $this->assertLogin($this->getOTP());

        $this->assertContains(
            'we are home',
            $this->home()
        );
    }

    public function testWrongOTP()
    {
        $this->assertLogin('9999999', 'google2fa view');
    }

    public function testLogout()
    {
        $this->assertContains(
            'google2fa view',
            $this->home()
        );

        $this->assertLogin($this->getOTP());

        $this->assertContains(
            'we are home',
            $this->home()
        );

        $this->assertContains(
            '',
            $this->call('POST', 'logout')->getContent()
        );

        $this->assertContains(
            'google2fa view',
            $this->home()
        );
    }

    public function testOldPasswords()
    {
        config(['google2fa.forbid_old_passwords' => true]);

        $this->assertContains(
            'google2fa view',
            $this->home()
        );

        $this->assertLogin($this->getOTP());

        $this->assertContains(
            'we are home',
            $this->home()
        );

        $this->assertContains(
            '',
            $this->call('POST', 'logout')->getContent()
        );

        $this->assertContains(
            'google2fa view',
            $this->home()
        );
    }

    public function testPasswordExpiration()
    {
        config(['google2fa.lifetime' => 1]);

        $this->assertLogin($this->getOTP());

        $this->assertContains(
            'we are home',
            $this->home()
        );

        Carbon::setTestNow(Carbon::now()->addMinutes(3));

        $this->assertContains(
            'google2fa view',
            $this->home()
        );
    }

    public function testGoogle2FAEmptyPassword()
    {
        $this->assertLogin('', 'cannot be empty');

        $this->assertLogin(null, 'cannot be empty');
    }
}
