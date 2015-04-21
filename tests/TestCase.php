<?php

abstract class TestCase extends PHPUnit_Framework_TestCase
{
  
    function __construct()
    {
        parent::__construct();
        $this->app = Slim\Slim::getInstance();
    }

    public function setUp()
    {
        $this->before();
    }

    public function before()
    {

    }

    public function tearDown()
    {
        $this->after();
    }

    public function after()
    {

    }
      
}