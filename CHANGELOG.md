## Change Log

## [1.1.2] - 2019-03-13
### Added
- New config item for empty OTP error messages: 'cannot_be_empty' => 'One Time Password cannot be empty.',  
- Tests to check for HTML error messages

## [1.1.1] - 2019-03-12
### Removed
- PHP 7.0 support

## [1.1.0] - 2019-09-10
### Added
- Laravel 6 support

## [1.0.0] - 2019-03-21
- Start using Google2FA QRCode as base class
- Support QRCode generation again
- Support API / Stateless requests

## [0.3.0] - 2019-03-20
- Upgrade to GoogleFA 5.0

## [0.2.1] - 2019-03-20
- Removed QRCode generation via Google2FA 4.0

## [0.2.0] - 2018-03-07
### Add
- Firing events: 
    - EmptyOneTimePasswordReceived
    - LoggedOut
    - LoginFailed
    - LoginSucceeded
    - OneTimePasswordExpired
    - OneTimePasswordRequested
### Changed
- Google2FA is not dependent from Middleware anymore 
- Small refactor
- Change license to MIT
- Move from phpspec tests to PHPUnit and Orchestra
### Removed
- Support for PHP 5.4-5.6 (should still work, but would have to be tested by users)

## [0.1.4] - 2017-12-05
### Add
- Support Laravel 5.2+

## [0.1.2] - 2016-06-22
### Fixed
- Fix middleware returning nulls

## [0.1.1] - 2016-06-22
### Fixed
- Keepalive time not being updated correctly

## [0.1.0] - 2016-06-21
### Added
- First version
