<?php

use App\Scrapers\Abreeza;

class AbreezaTest extends TestCase
{
  
  /**
   * @vcr unittest_abreeza_fetch
   */
  public function testCanFetch()
  {
    $abreeza = new Abreeza();
    $this->assertTrue($abreeza->fetch()->hasMovies());
  }
  
}