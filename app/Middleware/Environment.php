<?php

namespace App\Middleware;

use google\appengine\api\app_identity\AppIdentityService;

/**
 * Environment Middleware
 *
 * Detects the environment we are running in and sets the mode & debug
 * values accordingly.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */
class Environment extends \Slim\Middleware
{
  
  public function call()
  {
    
    if ( isset($_SERVER['APPLICATION_ID']) )
    {
      $this->app->config(array(
        'mode'  => 'production',
        'debug' => false
      ));
    }
    else
    {
      $this->app->config(array(
        'mode'  => 'development',
        'debug' => true
      ));
    }
    
    $this->app->setName(AppIdentityService::getApplicationId());
    
    $this->next->call();
    
  }
  
}