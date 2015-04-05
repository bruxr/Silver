<?php
/**
 * SM Malls Scraper
 *
 * Base class for all SM Scrapers providing common methods
 * for accessing the smcinema.com API.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */

namespace App\Scrapers;

use App\Exceptions\ParseException;
use App\Services\Log;

class SmMalls extends Base
{

  /**
   * The SM Cinema API
   *
   * @var string
   */
  const URL = 'https://smcinema.com/ajaxMovies.php';
  
  public function fetch()
  {
    $this->fetchMovies(static::BRANCH_CODE);
    return $this;
  }
  
  protected function fetchMovies($branch_code)
  {
    
  }

}
