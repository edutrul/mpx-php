# MPX for PHP

[![CircleCI](https://circleci.com/gh/Lullabot/mpx-php.svg?style=svg)](https://circleci.com/gh/Lullabot/mpx-php) [![Maintainability](https://api.codeclimate.com/v1/badges/cc44177e7a46c0d99d88/maintainability)](https://codeclimate.com/github/Lullabot/mpx-php/maintainability) [![Test Coverage](https://api.codeclimate.com/v1/badges/cc44177e7a46c0d99d88/test_coverage)](https://codeclimate.com/github/Lullabot/mpx-php/test_coverage)

## Quick Start

* PHP 7.0+
* Composer

`composer require lullabot/mpx-php`

## Logging

This library will log API actions that are transparent to the calling code. For
example, calling code should handle logging of invalid credentials, while this
library will log if an authentication was automatically refreshed during an
API request that resulted in a `401`.

If your application does not wish to log these actions at all, use
`\Psr\Log\NullLogger` for any constructors that require a
`\Psr\Log\LoggerInterface`.


## Overview of main classes

### Client
MPX API implementation of Guzzle ClientInterface. As a Client it doesn’t do anything extra but suppress errors to force a returning HTTP 200.
It also handles XML from responses

### UserSession
A wrapper around Client, implementing all the stuff from Guzzle ClientInterface, and adding sign and singout functionalities.

### User
An MPX user. Just username and password getters.

### Token
MPX authentication token that is returned by the platform after [sign in](https://docs.theplatform.com/help/wsf-signin-method)

### TokenCachePool
Cache of user authentication tokens. This class is a wrapper around a \Psr\Cache\CacheItemPoolInterface object
