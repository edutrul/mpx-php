<?php

/**
 * @file
 * Contains Mpx\AccountInterface.
 */

namespace Mpx;

use GuzzleHttp\ClientInterface;
use Pimple\Container;
use Psr\Log\LoggerInterface;

interface AccountInterface {

  /**
   * @param string $username
   * @param string $password
   * @param \GuzzleHttp\ClientInterface $client
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct($username, $password, ClientInterface $client, LoggerInterface $logger);

  /**
   * @param string $username
   * @param string $password
   * @param \Pimple\Container $container
   * @return $this
   */
  public static function create($username, $password, Container $container);

  /**
   * @return string
   */
  public function getUsername();

  /**
   * @return string
   */
  public function getPassword();

  /**
   * Set the current authentication token for the account
   *
   * @param string $token
   *   The token string.
   * @param int $expires
   *   A UNIX timestamp of when the token is set to expire.
   *
   * @throws \Mpx\Exception\InvalidTokenException
   */
  public function setToken($token, $expires);

  /**
   * Gets a current authentication token for the account.
   *
   * @param bool $fetch
   *   TRUE if a new token should be fetched if one is not available.
   *
   * @return string
   */
  public function getToken($fetch = TRUE);

  public function setExpires($expires);

  /**
   * @return int
   */
  public function getExpires();

  /**
   * Checks if the user's current token is valid.
   *
   * @return bool
   *   TRUE if the current token is valid, or FALSE if the token is not valid.
   */
  public function hasValidToken();

  /**
   * Checks if a token is valid.
   *
   * @param string $token
   *   The token string.
   * @param int $expires
   *   A UNIX timestamp of when the token is set to expire.
   *
   * @return bool
   *   TRUE if the token is valid, or FALSE if the token is not valid.
   */
  public static function isValidToken($token, $expires);

  /**
   * Signs in the user.
   *
   * @param int $duration
   *   The duration of the token, in milliseconds.
   * @param int $idleTimeout
   *   The idle timeout for the token, in milliseconds.
   *
   * @return $this
   *
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \RuntimeException
   * @throws \Mpx\Exception\InvalidTokenException
   */
  public function signIn($duration = NULL, $idleTimeout = NULL);

  /**
   * Signs out the user.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  public function signOut();

}
