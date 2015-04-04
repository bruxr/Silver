<?php

use App\Scrapers\Nccc;

class NcccTest extends TestCase
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