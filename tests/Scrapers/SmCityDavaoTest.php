<?php

use App\Scrapers\SmCityDavao;

class SmCityDavaoTest extends PHPUnit_Framework_TestCase
{
  
  /**
   * @vcr unittest_nccc_fetch
   */
  public function testCanFetch()
  {
    $sm = new SmCityDavao();
    $this->assertTrue($sm->fetch()->hasMovies());
  }
  
}