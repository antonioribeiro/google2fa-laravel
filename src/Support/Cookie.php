<?php

namespace PragmaRX\Google2FALaravel\Support;

use Illuminate\Support\Str;

trait Cookie
{
    /**
     * Get the 2FA remember cookie name.
     *
     * @return string
     */
    protected function get2FACookieName()
    {
        return 'google2fa_remember';
    }

    /**
     * Check if 2FA remember is enabled.
     *
     * @return bool
     */
    protected function is2FARememberEnabled()
    {
        return $this->config('remember_2fa', false) === true;
    }

    /**
     * Create an encrypted 2FA remember cookie payload.
     *
     * @param string $timestamp
     *
     * @return string
     */
    protected function create2FACookiePayload($timestamp)
    {
        // Generate a token that ties this cookie to the current auth session
        $token = $this->generate2FAToken();

        $payload = json_encode([
            'timestamp' => $timestamp,
            'device_id' => $this->getDeviceFingerprint(),
            'token'     => $token,
        ]);

        return encrypt($payload);
    }

    /**
     * Generate and store a 2FA token for the current session.
     * This token is regenerated on each new authentication.
     *
     * @return string
     */
    protected function generate2FAToken()
    {
        $token = Str::random(40);

        // Store in session - this will be different on each new auth
        $this->sessionPut('2fa_token', $token);

        return $token;
    }

    /**
     * Get the expected 2FA token for the current session.
     *
     * @return string|null
     */
    protected function getExpected2FAToken()
    {
        return $this->sessionGet('2fa_token');
    }

    /**
     * Get a device fingerprint from the request.
     * This helps ensure the cookie is device-specific.
     *
     * @return string
     */
    protected function getDeviceFingerprint()
    {
        $request = $this->getRequest();

        // Use user agent + IP for basic device fingerprinting
        // In production, you might want to use only user agent to handle
        // users with dynamic IPs
        return sha1(
            $request->userAgent().
            $request->ip()
        );
    }

    /**
     * Set the 2FA remember cookie.
     *
     * @param string $timestamp
     *
     * @return void
     */
    protected function set2FARememberCookie($timestamp)
    {
        if (!$this->is2FARememberEnabled()) {
            return;
        }

        $cookie = cookie(
            $this->get2FACookieName(),
            $this->create2FACookiePayload($timestamp),
            $this->get2FACookieLifetime()
        );

        // Queue the cookie to be sent with the response
        $this->getRequest()->attributes->set('google2fa_cookie', $cookie);
    }

    /**
     * Get the lifetime for the 2FA remember cookie in minutes.
     * Defaults to lifetime config or 30 days (43200 minutes).
     *
     * @return int
     */
    protected function get2FACookieLifetime()
    {
        $lifetime = $this->config('lifetime', 0);

        // If lifetime is 0 (eternal), use 30 days as a reasonable default
        // Users can override this by implementing custom logic
        return $lifetime > 0 ? $lifetime : 43200; // 30 days
    }

    /**
     * Get and validate the 2FA remember cookie.
     *
     * @return array|null Returns the payload array if valid, null otherwise
     */
    public function getValid2FACookie()
    {
        if (!$this->is2FARememberEnabled()) {
            return null;
        }

        $cookieValue = $this->getRequest()->cookie($this->get2FACookieName());

        if (empty($cookieValue)) {
            return null;
        }

        try {
            $payload = json_decode(decrypt($cookieValue), true);

            // Validate the payload structure
            if (!isset($payload['timestamp']) || !isset($payload['device_id']) || !isset($payload['token'])) {
                return null;
            }

            // Validate device fingerprint
            if ($payload['device_id'] !== $this->getDeviceFingerprint()) {
                return null;
            }

            // Validate that the token matches the current session
            // This ensures the cookie is tied to the current auth session
            $expectedToken = $this->getExpected2FAToken();
            if ($expectedToken === null || $payload['token'] !== $expectedToken) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            // Decryption failed or JSON decode failed
            return null;
        }
    }

    /**
     * Check if the 2FA cookie is still valid based on lifetime.
     *
     * @param array $payload
     *
     * @return bool
     */
    protected function is2FACookieValid(array $payload)
    {
        $lifetime = $this->config('lifetime', 0);

        // If lifetime is 0, it's eternal
        if ($lifetime == 0) {
            return true;
        }

        $cookieTime = \Carbon\Carbon::parse($payload['timestamp']);
        $minutesSince = \Carbon\Carbon::now()->diffInMinutes($cookieTime, true);

        return $minutesSince <= $lifetime;
    }

    /**
     * Clear the 2FA remember cookie.
     *
     * @return void
     */
    protected function forget2FARememberCookie()
    {
        // Create a cookie with expiration in the past to delete it
        $cookie = new \Symfony\Component\HttpFoundation\Cookie(
            $this->get2FACookieName(),
            null,
            -1 // Negative timestamp to ensure deletion
        );

        $this->getRequest()->attributes->set('google2fa_cookie', $cookie);
    }
}
