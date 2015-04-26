<?php

abstract class TestCase extends PHPUnit_Framework_TestCase
{
  
    function __construct()
    {
        parent::__construct();
        global $app;
        $this->app = $app;
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