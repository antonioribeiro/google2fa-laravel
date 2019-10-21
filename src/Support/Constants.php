<?php

namespace PragmaRX\Google2FALaravel\Support;

class Constants
{
    const CONFIG_PACKAGE_NAME = 'google2fa';

    const SESSION_AUTH_PASSED = 'auth_passed';

    const SESSION_AUTH_TIME = 'auth_time';

    const SESSION_OTP_TIMESTAMP = 'otp_timestamp';

    const QRCODE_IMAGE_BACKEND_EPS = 'eps';

    const QRCODE_IMAGE_BACKEND_SVG = 'svg';

    const QRCODE_IMAGE_BACKEND_IMAGEMAGICK = 'imagemagick';

    const OTP_EMPTY = 'empty';

    const OTP_VALID = 'valid';

    const OTP_INVALID = 'invalid';
}
