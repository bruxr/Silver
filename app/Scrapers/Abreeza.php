<?php
/**
 * Abreeza Scraper
 *
 * Parses schedules for Abreeza.
 *
 * @package Silver
 * @author brux
 * @since 0.1.0
 */

namespace App\Scrapers;

use App\Exceptions\ParseException;
use Carbon\Carbon;

class Abreeza extends Base
{
  
  const SLUG = 'abreeza';

  /**
   * The page we will be scraping.
   *
   * @var string
   */
  const URL = 'http://www.sureseats.com/theaters/search.asp?tid=ABRZ';
  
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
    
    $blocks = $this->extractBlocks();
    $this->processBlocks($blocks);
    
    return $this;
    
  }
  
  protected function extractBlocks()
  {
    
    $tables = $this->page->find('.rounded-half-nbp table[width=135]');

    // Log a message if we failed to extract movies
    if ( empty($tables) )
    {
      throw new ParseException('Abreeza', 'Failed to extract movies!');
    }
    
    return $tables;
    
  }
  
  protected function processBlocks($blocks)
  {
    foreach ( $blocks as $block )
    {
      $this->processMovie(pq($block));
    }
  }
  
  protected function processMovie($block)
  {

    $is_3d = false;
    
    // If we failed to extract a title, log then stop.
    $movie = strtolower(trim($block->find('.SEARCH_TITLE')->text()));
    $movie = $this->cleanMovieTitle($movie);
    if ( empty($movie) )
    {
      $this->logger->addCritical('[Abreeza] Failed to extract a movie title.');
      return;
    }
    else
    {
      // Extract 3D suffix
      if ( preg_match('/^\(3d\)/', $movie) )
      {
        $is_3d = true;
        $movie = preg_replace('/^\(3d\)\s/', '', $movie);
      }
      
      // Create a new record if the movie doesn't exist yet
      if ( ! isset($this->movies[$movie]) )
      {
        $this->movies[$movie] = [
          'title' => $movie,
          'screening_times' => []
        ];
      }
    }
    
    // Make sure we have a valid cinema
    $cinema = $block->find('tr:eq(0)')->text();
    $cinema = trim($cinema);
    if ( ! preg_match('/^Cinema [1-4]$/', $cinema) )
    {
      throw new ParseException('Abreeza', 'Invalid cinema name.', $cinema);
    }
    else
    {
      $cinema = (int) str_replace('Cinema ', '', $cinema);
    }

    // Extract the MTRCB rating and make sure it is a correct one.
    $rating = $block->find('.SEARCH_RATING')->text();
    $rating = str_replace('Rating: ', '', $rating);
    if ( ! in_array($rating, static::$RATINGS) )
    {
      $this->logger->addWarning(sprintf('[Abreeza] "%s" is not a valid MTRCB rating for the movie "%s". Removing it for now.', $rating, $movie));
    }
    else
    {
      $this->movies[$movie]['rating'] = $rating;
    }
    
    // Extract the price
    $price = str_replace('Price: ', '', $block->find('.SEARCH_PRICE')->text());
    $price = trim($price);
    if ( ! preg_match('/^[0-9]+$/', $price) )
    {
      $this->logger->addWarning(sprintf('[Abreeza] "%s" is not a valid ticket price for the movie "%s".', $price, $movie));
      $price = null;
    }
    else
    {
      $price = (int) $price;
    }
    
    // Extract the date
    $date = trim($block->find('.SEARCH_DATE')->text());
    if ( ! preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{1,2},\s20[0-9]{2}$/i', $date) )
    {
      throw new ParseException('Abreeza', 'Invalid screening date.', $date);
    }
    
    // Extract screening times and build the date objects
    foreach ( $block->find('.SEARCH_SCHED') as $s )
    {
      
      // Validate the time
      $s = trim(pq($s)->text());
      if ( ! preg_match('/((?:1[012]|[1-9]):[0-5][0-9]\s(?i)(?:am|pm))/', $s, $matches) )
      {
        $this->logger->addWarning(sprintf('[Abreeza] "%s" is not a valid time for "%s". Skipping.', $s, $movie));
        continue;
      }
      else
      {
        $s = $matches[1];
      }

      $arr = [
        'cinema'  => $cinema,
        'time'    => Carbon::parse($date . ' ' . $s)
      ];

      // Add ticket price if we have one
      if ( $price !== null )
      {
        $arr['ticket']['price'] = $price;
      }
      
      // Add a 3D designator if it is one.
      if ( $is_3d )
      {
        $arr['format'] = '3D';
      }
      
      $this->movies[$movie]['screening_times'][] = $arr;
      
    }

  }
  
  protected function consolidate($movies)
  {
    
    $m = [];
    
    $map = [];
    $i = 0;
    
    foreach ( $movies as $movie )
    {
      if ( isset($map[$movie['title']]) )
      {
        $index = $map[$movie['title']];
        $m[$index]['screening_times'] = array_merge($m[$index]['screening_times'], $movie['screening_times']);
        if ( isset($movie['rating']) )
        {
          $m[$index]['rating'] = $movie['rating'];
        }
      }
      else
      {
        $map[$movie['title']] = $i;
        $m[$i] = $movie;
        $i++;
      }
    }
    return $m;
  }

}
