<?php

use App\Scrapers\GaisanoMall;

class GaisanoMallTest extends PHPUnit_Framework_TestCase
{
  
  /**
   * @vcr unittest_gmall_fetch
   */
  public function testCanFetch()
  {
    $gmall = new GaisanoMall(getenv('FB_ACCESS_TOKEN'));
    $this->assertTrue($gmall->fetch()->hasMovies());
  }
  
}