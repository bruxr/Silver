<?php

use App\Scrapers\Base;

class BaseTest extends PHPUnit_Framework_TestCase
{
  
  public function setUp()
  {
    $this->stub = $this->getMockForAbstractClass('App\Scrapers\Base');
  }
  
  public function testHasMovies()
  {
    $this->assertFalse($this->stub->hasMovies());
  }
  
  public function testCleanMovieTitle()
  {
    $this->assertEquals('Awesome Movie', $this->stub->cleanMovieTitle('Awe<strong>some</strong> Mo<i>vie'));
  }
  
  public function testCleanCinemaName()
  {
    $this->assertEquals('Cinema 5', $this->stub->cleanCinemaName('Cinema 5 (DIGITAL)'));
  }
  
  public function testUnknownCinemaName()
  {
    $this->assertEquals('Unknown Cinema', $this->stub->cleanCinemaName('Blackbox Theatre'));
  }
  
}