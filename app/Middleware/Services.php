<?php

namespace App\Middleware;

/**
 * Services Middleware
 *
 * Adds several services we need to the application object.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */
class Services extends \Slim\Middleware
{
  
  public function call()
  {
    
    $this->next->call();
    
  }
  
}