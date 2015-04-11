<?php
/**
 * Gaisano Malls Scraper
 *
 * Base class for all Gaisano Mall scrapers, providing common methods
 * for processing schedule posts.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */

namespace App\Scrapers;

use App\Exceptions\ParseException;
use App\Services\Log;
use App\Services\ClientFactory;

use DatePeriod;
use DateInterval;
use Carbon\Carbon;

class GaisanoMalls extends Base
{

  /**
   * The GMall Cinemas API endpoint
   *
   * @var string
   */
  const URL = 'https://graph.facebook.com/v2.3/gmallcinemas/feed';
  
  protected $access_token;
  
  function __construct($access_token)
  {
    $this->access_token = $access_token;
  }
  
  public function fetch()
  {
    $post = $this->getPost();
    $this->parsePost($post);
    dump($this->movies);
    return $this;
  }
  
  protected function getPost()
  { 
    $client = ClientFactory::create();
    $opts = ['query' => [
      'access_token' => $this->access_token,
      'fields'       => 'message'
    ]];
    $resp = $client->get(self::URL, $opts)->getBody();
    $resp = json_decode($resp, true);
    
    // Find the schedule post
    $post = null;
    foreach ( $resp['data'] as $d )
    {
      if ( isset($d['message']) && preg_match('/^SKED FOR/', $d['message']) )
      {
        $post = $d['message'];
      }
    }
    
    // Throw an error if we can't find a schedule post
    if ( $post === null )
    {
      throw new ParseException('Gmall', 'Failed to find schedule post.');
    }
    
    return $post;
  }
  
  protected function parsePost($post)
  { 
    $lines = explode("\n", $post);
    $dates = $this->extractDates($lines[0]);
    
    // Remove the first two lines
    $lines = array_slice($lines, 2);
    $res = $this->processLines($lines);
    $this->build($dates, $res[static::MALL]);
  }
  
  protected function extractDates($post)
  {
    if ( preg_match('/SKED FOR (.+)\.\s(?:ALL\s)?MOVIES/i', $post, $matches) )
    {
      // Try to replace the day names with the current year
      $current_year = date('Y');
      $str = preg_replace('/\([A-Z]+\)/', $current_year, $matches[1]);
      
      // Extract the dates
      $str = explode(' - ', $str);
      if ( count($str) != 2 )
      {
        throw new ParseException('Gmall', 'Unexpected date range.', implode(' - ', $str));
      }
      
      // Make sure we have the correct dates
      foreach ( $str as $s )
      {
        if ( ! preg_match('/^(?:JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|NOVEMBER|DECEMBER)\s[0-9]{1,2},?\s20[0-9]{2}$/', $s) )
        {
          throw new ParseException('Gmall', 'Invalid date.', $s);
        }
      }
      
      // Build the date period
      $start = Carbon::parse($str[0]);
      $end = Carbon::parse($str[1]);
      return new DatePeriod($start, new DateInterval('P1D'), $end);
    }
    else
    {
      throw new ParseException('Gmall', 'Failed to extract screening dates.');
    }
  }
  
  protected function processLines($lines)
  {
    $mall = null;
    $cinema = null;
    $movie = null;
    $mtrcb_rating = null;
    $is_3d = false;
    $times = null;
    $prices = null;
    $res = [];
    foreach ( $lines as $index => $line )
    {
      
      // Catch mall lines
      if ( in_array($line, ['DAVAO', 'TORIL', 'TAGUM']) )
      {
        $mall = $line;
        continue;
      }
      
      // Catch cinema and the movie title below it
      if ( preg_match('/^CINEMA [1-9]$/', $line) )
      {
        $cinema = $this->processCinemaName($line);
        $movie = $this->processMovieTitle($lines[$index + 1]);
        
        // Catch 3D movies
        if ( preg_match('/\(3d\)$/i', $movie) )
        {
          $is_3d = true;
          $movie = preg_replace('/\s?\(3d\)$/i', '', $movie);
        }
        
        continue;
      }
      
      // Catch MTRCB ratings
      if ( preg_match('/^(PG-13|PG13|G|PG|R13|R16|R18)\s?-\s?/', $line, $matches) )
      {
        $mtrcb_rating = $this->processMtrcbRating($matches[1]);
        continue;
      }
      
      // Catch the screening times
      if ( preg_match('/([0-9]{1,2}:[0-9]{2}\|?)+/', $line) )
      {
        $times = $this->processScreeningTimes($line);
        
        // Catch the prices
        if ( preg_match('/(P[0-9]{3}\|?)+/', $lines[$index + 1]) )
        {
          $prices = $this->processPrices($lines[$index + 1]);
        }
        
        // Start building the schedules
        if ( $mall === null )
        {
          $mall = 'DAVAO'; // forgotten mall headers
        }
        $res[$mall][$movie][] = compact('cinema', 'mtrcb_rating', 'times', 'prices', 'is_3d');
        
        // Done processing movie, reset.
        $movie = $mtrcb_rating = $times = $prices = null;
        $is_3d = false;
        
        // If the next block is a movie, acknowledge
        if ( isset($lines[$index + 3]) && ! preg_match('/CINEMA/', $lines[$index + 3]) )
        {
          $movie = $this->processMovieTitle($lines[$index + 3]);
        }
      }
      
    }
    
    return $res;
  }
  
  protected function processCinemaName($str)
  {
    return (int) str_replace('CINEMA ', '', $str);
  }
  
  protected function processMovieTitle($str)
  {
    $title = trim($str);
    $title = $this->cleanMovieTitle($title);
    $title = strtolower($title);
    return $title;
  }
  
  protected function processMtrcbRating($str)
  {
    switch ( $str )
    {
      case 'G':
        $rating = 'G';
        break;
      case 'PG13':
      case 'PG-13':
        $rating = 'PG';
        break;
      case 'R13':
        $rating = 'R-13';
        break;
      case 'R16':
        $rating = 'R-16';
        break;
      case 'R18':
        $rating = 'R-18';
        break;
      default:
        Log::error(sprintf('[Gmall] Ignoring unknown MTRCB rating "%s".', $rating));
        $rating = null;
        break;
    }
    return $rating;
  }
  
  protected function processScreeningTimes($str)
  {
    $mornings = [10, 11];
    $str = trim($str);
    preg_match_all('/[0-9]{1,2}:[0-9]{2}/', $str, $matches);
    
    foreach ( $matches[0] as $t )
    {
      $t = trim($t);
      
      $hour = substr($t, 0, 2);
      if ( in_array($hour, $mornings) )
      {
        $t .= ' AM';
      }
      else
      {
        $t .= ' PM';
      }
      
      $times[] = $t;
    }
    return $times;
  }
  
  protected function processPrices($str)
  {
    $times = trim($str);
    $times = str_replace('P', '', $times);
    $times = explode('|', $times);
    $times = array_map('intval', $times);
    return $times;
  }
  
  protected function build($dates, $data)
  {
    
    foreach ( $data as $movie => $items )
    {
      
      if ( ! isset($this->movies[$movie]) )
      {
        $this->movies[$movie] = [
          'title' => $movie,
          'screening_times' => []
        ];
      }
      
      foreach ( $items as $i )
      {
        $this->movies[$movie]['mtrcb_rating'] = $i['mtrcb_rating'];
        foreach ( $i['times'] as $time )
        {
          
          foreach ( $dates as $date )
          {
            $arr = [
              'mall'    => static::SLUG,
              'cinema'  => $i['cinema'],
              'time'    => Carbon::parse($date->format('Y-m-d') . ' ' . $time),
              'ticket'  => []
            ];
            
            // Process multiple prices
            foreach ( $i['prices'] as $p )
            {
              $arr['ticket'][]['price'] = $p;
            }
            if ( count($i['prices']) > 1 )
            {
              if ( $arr['ticket'][0]['price'] < $arr['ticket'][1]['price'] )
              {
                $arr['ticket'][0]['name'] = 'Lower Deck';
                $arr['ticket'][1]['name'] = 'Upper Deck';
              }
              else
              {
                $arr['ticket'][0]['name'] = 'Upper Deck';
                $arr['ticket'][1]['name'] = 'Lower Deck';
              }
            }
            
            // Add 3D format if needed
            if ( $i['is_3d'] )
            {
              $arr['format'] = '3D';
            }
            
            $this->movies[$movie]['screening_times'][] = $arr;
          }
          
        }
      }
    }
    
  }

}
