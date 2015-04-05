<?php

use App\Scrapers\Nccc;

class NcccTest extends PHPUnit_Framework_TestCase
{
  
  /**
   * @vcr unittest_nccc_fetch
   */
  public function testCanFetch()
  {
    $nccc = new Nccc();
    $this->assertTrue($nccc->fetch()->hasMovies());
  }
  
}