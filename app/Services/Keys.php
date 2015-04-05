<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use Exception;

/**
 * Keys
 *
 * The Keys singleton is responsible for managing the application's keys
 * defined in secrets.yml.
 *
 * To use secret keys, invoke Keys::get('dropbox.api_key') passing
 * the name of the secret key you want to use.
 *
 * Take note that this class supports grouping by dot notation. Top level
 * keys with sub items are designated as groups, e.g:
 *
 * dropbox:
 *   api_key: 1234
 *   secret_token: 5678
 *
 * Which can be acccessed as given before: Keys::get('dropbox.api_key');
 *
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
class Keys
{
  
  private $keys;
  
  private static $instance;
  
  /**
   * I'll make sure no one can harm you baby.
   */
  protected function __construct()
  {
    $this->keys = Yaml::parse(file_get_contents(ROOT . '/secrets.yml'));
  }
  
  /**
   * Returns a secret. Shh.
   *
   * @param string $key to the secret
   * @return string
   */
  public function fetch($key)
  {
    
    $secret = null;
    
    if ( strpos($key, '.') === false )
    {
      $secret = $this->keys[$key]; 
    }
    else
    {
      list($group, $key) = explode('.', $key);
      $secret =  $this->keys[$group][$key];
    }
    
    if ( $secret )
    {
      return $secret;
    }
    else
    {
      throw new Exception("Cannot find the secret \"$key\".");
    }
    
  }
  
  /**
   * Returns our only instance.
   *
   * @return Keys
   */
  public static function getInstance()
  {
    if ( is_null(self::$instance) )
    {
      self::$instance = new self();
    }
    
    return self::$instance;
  }
  
  /**
   * Static wrapper to Keys::read()
   *
   * @param string $key to the secret
   * @return string
   */
  public static function get($key)
  {
    return self::getInstance()->fetch($key);
  }
  
}