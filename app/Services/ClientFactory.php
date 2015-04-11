<?php namespace App\Services;

use GuzzleHttp\Client;

/**
 * Client Factory
 *
 * Creates Guzzle HTTP Clients with our ID headers added in. These headers
 * contain the name of our bot and the name of the owner.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */
class ClientFactory
{
  
  protected static $HEADERS = [
    'User-Agent: Silverbot 1.0',
    'X-Owner-Address: brux <brux.romuar@gmail.com>'
  ];
  
  /**
   * Returns a new instance of the Guzzle HTTP client with
   * our headers added in. Set $identify to false to
   * disable the ID headers.
   *
   * @param bool $identify whether to send the ID headers or nor
   * @return GuzzleHttp\Client
   */
  public static function create($identify = true)
  {
    return new Client(['headers' => static::$HEADERS]);
  }
  
}