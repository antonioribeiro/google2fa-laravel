<?php

namespace PragmaRX\Google2FALaravel\Tests;

use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Tests\Constants;
use PragmaRX\Google2FALaravel\Exceptions\InvalidOneTimePassword;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use PragmaRX\Google2FALaravel\Tests\Support\User;

class Google2FaLaravelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \PragmaRX\Google2FALaravel\ServiceProvider::class,
            \Illuminate\Auth\AuthServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Google2FA' => \PragmaRX\Google2FALaravel\Facade::class,
            'Auth' => \Illuminate\Support\Facades\Auth::class,
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

    protected function postLogin()
    {
        $this->assertEquals(
            'google2fa passed',
            $this->call('POST', 'login', ['one_time_password' => $this->getOTP()])->getContent()
        );
    }

    public function setUp()
    {
        parent::setup();

        $this->app->make('Illuminate\Contracts\Http\Kernel')->pushMiddleware('Illuminate\Session\Middleware\StartSession');

        \View::addLocation(__DIR__.'/views');

        $this->loginUser();
    }

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
        $this->assertEquals(
            "google2fa view\n",
            $this->home()
        );
    }

    public function testGoogle2FAPostPasses()
    {
        $this->postLogin();

        $this->assertEquals(
            'we are home',
            $this->home()
        );
    }

    public function testGoogle2FAEmptyPassword()
    {
        $this->assertContains(
            "cannot be empty",
            $this->call('POST', 'login', ['one_time_password' => null])->getContent()
        );
    }

    public function testLogout()
    {
        $this->assertEquals(
            "google2fa view\n",
            $this->home()
        );

        $this->assertEquals(
            'google2fa passed',
            $this->call('POST', 'login', ['one_time_password' => $this->getOTP()])->getContent()
        );

        $this->assertEquals(
            "we are home",
            $this->home()
        );

        $this->assertEquals(
            '',
            $this->call('POST', 'logout')->getContent()
        );

        $this->assertEquals(
            "google2fa view\n",
            $this->home()
        );
    }

    public function testOldPasswords()
    {
        config(['google2fa.forbid_old_passwords' => true]);

        $this->assertEquals(
            "google2fa view\n",
            $this->home()
        );

        $this->assertEquals(
            'google2fa passed',
            $this->call('POST', 'login', ['one_time_password' => $this->getOTP()])->getContent()
        );

        $this->assertEquals(
            "we are home",
            $this->home()
        );

        $this->assertEquals(
            '',
            $this->call('POST', 'logout')->getContent()
        );

        $this->assertEquals(
            "google2fa view\n",
            $this->home()
        );
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
}
