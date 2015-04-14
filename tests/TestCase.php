<?php
class TestCase extends PHPUnit_Framework_TestCase
{
  
  function __construct()
  {
    parent::__construct();
    $this->app = Slim\Slim::getInstance();
  }
  
}