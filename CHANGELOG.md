## Change Log

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
