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

use Carbon\Carbon;

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
    $movies = $this->fetchMovies();
    $this->processMovies($movies);
    dump($this->movies);
    return $this;
  }
  
  protected function query($method, $args = [])
  {
    $defaults = [
      'method' => $method,
      'branch_code' => static::BRANCH_CODE
    ];
    $args = array_merge($defaults, $args);
    
    $context = $this->createContext($args);
    $resp = file_get_contents(self::URL, false, $context);
    
    $resp = @json_decode($resp, true);
    if ( $resp === null )
    {
      throw new ParseException('SM', 'Failed to parse response. ('. json_last_error() .')', $args);
    }
    return $resp;
    
  }
  
  protected function createContext($data)
  {
    $data = http_build_query($data);
    $headers = [
      'Accept: application/json',
      'User-Agent: Silverbot',
      'X-Contact: brux <brux.romuar@gmail.com>'
    ];
    
    return stream_context_create([
      'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => $data
      ]
    ]);
  }
  
  protected function fetchMovies()
  {
    return $this->query('listMovies');
  }
  
  protected function processMovies($movies)
  {
    foreach ( $movies as $movie )
    {
      $this->processMovie($movie);
    }
  }
  
  protected function processMovie($movie)
  {
    
    $resp = $this->query('listScreeningTime', ['movie_name' => $movie['movie_name']]);
    
    // Process the title
    $movie = $this->cleanMovieTitle(trim($movie['movie_name']));
    $movie = strtolower($movie);
    if ( ! isset($this->movies[$movie]) )
    {
      $this->movies[$movie] = [
        'title' => $movie,
        'screening_times' => []
      ];
    }
    
    // Start processing each record
    foreach ( $resp as $cinema => $items )
    {
      foreach ( $items as $r )
      {
      
        $cinema = $this->processCinema($r['CinemaCode']);
        $format = $this->extractFormat($r['FilmFormat']);
        $price = $this->processPrice($movie, $r['Price']);
        $time = $this->processTime($r['StartTime']);
        $rating = $this->processMtrcbRating($r['MtrcbRating']);
      
        $this->movies[$movie]['rating'] = $rating;
      
        $arr = [
          'cinema' => $cinema,
          'time' => $time,
          'ticket' => [
            'price' => $price
          ]
        ];
      
        if ( $format == '2D' && $cinema == 'IMAX' )
        {
          $arr['format'] = 'IMAX';
        }
        elseif ( $format != '2D' )
        {
          $arr['format'] = $format;
        }
      
        $this->movies[$movie]['screening_times'][] = $arr;
      }
    }
    
  }
  
  protected function processCinema($cinema)
  {
    $cinema = trim($cinema);
    if ( ! preg_match('/^([0-9]|IMAX)$/', $cinema) )
    {
      throw new ParseException('SM', 'Invalid cinema.', $cinema);
    }
    elseif ( $cinema != 'IMAX' )
    {
      $cinema = (int) $cinema;
    }
    return $cinema;
  }
  
  protected function extractFormat($format)
  {
    $format = trim($format);
    if ( $format == 'F2D' || $format == 'STANDARD' )
    {
      return '2D';
    }
    elseif ( $format == 'F3D' )
    {
      return '3D';
    }
    else
    {
      throw new ParseException('SM', 'Unknown film format.', $format);
    }
  }
  
  protected function processPrice($movie, $price)
  {
    $int_price = (int) $price;
    if ( $int_price == 0 )
    {
      throw new ParseException('SM', 'Failed to convert price to int for the movie '. $movie, $price);
    }
    return $int_price;
  }

  protected function processTime($time)
  {
    return Carbon::parse($time);
  }

  protected function processMtrcbRating($rating)
  {
    $rating = trim($rating);
    if ( $rating == 'NYR' )
    {
      return null;
    }
    elseif ( ! in_array($rating, self::$RATINGS) )
    {
      Log::warning(sprintf('[SM] Unknown MTRCB rating %s. Ignoring rating.', $rating));
      return null;
    }
    else
    {
      return $rating;
    }
  }

}
