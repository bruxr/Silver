<?php
/**
 * NCCC Mall of Davao Scraper
 *
 * Parses schedules for Abreeza.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */

namespace App\Scrapers;

use App\Exceptions\ParseException;

use DatePeriod;
use DateInterval;

use Carbon\Carbon;
use Log;

class Nccc extends Base
{
  
  const SLUG = 'nccc';

  /**
   * The page we will be scraping.
   *
   * @var string
   */
  const URL = 'http://nccc.com.ph/main/page/cinema';
  
  /**
   * Contains the phpQuery object wrapping the page we will be scraping.
   *
   * @var phpQuery
   */
  protected $page;
  
  /**
   * Fetches schedules from the page.
   *
   * @return this
   */
  public function fetch()
  {
    
    $this->page = $this->loadPage(self::URL);
  
    $this->extractBlocks();
    
    $period = $this->extractDatePeriod();
    $this->insertDatePeriod($period);
    
    return $this;
    
  }
  
  protected function extractDatePeriod()
  {

    $date = $this->page->find('.movie-info-contact:eq(0)')->text();
    $date = str_replace('Running Date: ', '', $date);
    $dates = explode(' - ', $date);
    
    // Make sure we have valid dates
    foreach ( $dates as $date )
    {
      if ( ! preg_match('/(January|February|March|April|May|June|July|August|September|October|November|December)\s[0-3][0-9],\s20[0-9]{2}/i', $date) )
      {
        throw new ParseException('NCCC', 'Invalid screening date.', $date);
      }
    }
    
    // Create the interval.
    $begin = Carbon::parse($dates[0]);
    $end = Carbon::parse($dates[1])->addDay(); // Add 1 day b/c DatePeriod excludes the end date.
    return new DatePeriod($begin, new DateInterval('P1D'), $end);

  }
  
  protected function extractBlocks()
  {
    $blocks = $this->page->find('.movie-thumbnail-nowshowing');
    foreach ( $blocks as $block )
    {
      $block = pq($block);
      $this->processBlock($block);
    }
  }
  
  protected function processBlock($block)
  {
    
    // Extract the cinema number
    $cinema = trim($block->find('div:eq(0)')->attr('class'));
    if ( ! preg_match('/^cinema[1-4]$/', $cinema) )
    {
      throw new ParseException('NCCC', 'Invalid cinema name.', $cinema);
    }
    $cinema = (int) str_replace('cinema', '', $cinema);
    
    // Start processing the body.
    $body = trim($block->find('.movie-title')->html());
    $body = explode('<br>', $body);
    
    $movie = null;
    $reached_af = false;
    $is_3d = false;
    
    foreach ( $body as $i => $line )
    {
      
      // Try to find the title which is surrounded by <b> tags
      if ( preg_match('/^\<b\>(.+)\<\/b\>$/i', $line, $matches) )
      {
        $movie = $this->cleanMovieTitle($matches[1]);
        $movie = strtolower($movie);

        // Catch 3D movies (designated by having a 3D suffix in the title)
        if ( preg_match('/3d$/', $movie) )
        {
          $is_3d = true;
        }

        // Add the movie to our array if it doesn't exist yet
        if ( ! isset($this->movies[$movie]) )
        {
          $this->movies[$movie] = [
            'title' => $movie,
            'screening_times' => []
          ];
        }
      }
      // Catch MTRCB ratings
      elseif ( preg_match('/^(PG-13|GP|G|PG|R-13|R-16|R-18)$/', $line) )
      {
        $rating = trim($line);
        
        if ( $rating == 'PG-13' )
        {
          $rating = 'PG';
        }
        elseif ( $rating == 'GP' )
        {
          $rating = 'G';
        }
        
        if ( ! in_array($rating, static::$RATINGS) )
        {
          Log::warning(sprintf('[NCCC] Unknown MTRCB rating "%s", removing it for now.', $rating));
        }
        else
        {
          $this->movies[$movie]['rating'] = $rating;
        }
      }
      // Catch screening times
      elseif ( preg_match('/((?:1[012]|[1-9]):[0-5][0-9])/', $line, $matches) )
      {
        $time = $matches[1];
        if ( ! $reached_af && preg_match('/^(1|2):/', $time) )
        {
          $reached_af = true;
        }
        if ( $reached_af || $time == '12:00' )
        {
          $time .= ' PM';
        }
        else
        {
          $time .= ' AM';
        }
        
        $arr = [
          'mall' => 'nccc',
          'cinema' => $cinema,
          'time' => $time
        ];
        
        if ( $is_3d )
        {
          $arr['format'] = '3D';
        }
        
        $this->movies[$movie]['screening_times'][] = $arr;
      }
      // If this is an unknown line, log it so we know.
      elseif ( $line != 'Schedule' && $line != '' )
      {
        Log::notice(sprintf('[NCCC] Unknown line "%s" cannot be processed.', $line));
      }
      
    }
    
  }
  
  protected function insertDatePeriod($period)
  {
    foreach ( $this->movies as $movie )
    {
      $screenings = $movie['screening_times'];
      $this->movies[$movie['title']]['screening_times'] = [];
      foreach ( $screenings as $s )
      {
        foreach ( $period as $p )
        {
          $new = $s;
          $new['time'] = Carbon::parse($p->format('Y-m-d') . ' ' . $s['time']);
          $this->movies[$movie['title']]['screening_times'][] = $new;
        }
      }
    }
  }

}
