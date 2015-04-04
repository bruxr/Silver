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

use Log;
use Exception;

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
    $movies = $this->processBlocks($blocks);
    $this->movies = $this->consolidate($movies);
    
    return $this;
    
  }
  
  protected function extractBlocks()
  {
    
    $tables = $this->page->find('.rounded-half-nbp table[width=135]');

    // Log a message if we failed to extract movies
    if ( empty($tables) )
    {
      throw new Exception('Failed to extract Abreeza movies!');
    }
    
    return $tables;
    
  }
  
  protected function processBlocks($blocks)
  {
    $movies = [];
    foreach ( $blocks as $block )
    {
      $movies[] = $this->processMovie($block);
    }
    return $movies;
  }
  
  protected function processMovie($movie)
  {

    $movie = pq($movie);
    $m = [];
    $m['screening_times'] = [];
    
    $cinema = $movie->find('tr:eq(0)')->text();
    $m['title'] = strtolower(trim($movie->find('.SEARCH_TITLE')->text()));

    // If we failed to extract a title, log then stop.
    if ( empty($m['title']) )
    {
      Log::critical('[Abreeza] Failed to extract a movie title.');
      return;
    }

    // Extract the MTRCB rating and make sure it is the correct one.
    $m['rating'] = str_replace('Rating: ', '',   $movie->find('.SEARCH_RATING')->text());
    if ( ! in_array($m['rating'], static::$RATINGS) )
    {
      unset($m['rating']);
    }
  
    // Detect 3D movies
    if ( preg_match('/^\(3d\)/', $m['title']) )
    {
      $is_3d = true;
      $m['title'] = preg_replace('/^\(3d\)\s/', '', $m['title']);
    }
    else
    {
      $is_3d = false;
    }

    // Clean up the title
    $m['title'] = $this->cleanMovieTitle($m['title']);
    
    // Extract the price
    $price = str_replace('Price: ', '', $movie->find('.SEARCH_PRICE')->text());
    $price = intval(trim($price));
    
    // Extract the date
    $date = $movie->find('.SEARCH_DATE')->text();
    
    // Extract screening times and build the date objects
    foreach ( $movie->find('.SEARCH_SCHED') as $s )
    {
      
      $s = pq($s)->text();
      $s = substr($s, 0, strlen($s) - 2);
      $arr = [
        'cinema'  => $cinema,
        'time'    => $date . ' ' . $s,
        'ticket'  => [
          'price'   => $price
        ]
      ];
      
      if ( $is_3d )
      {
        $arr['format'] = '3D';
      }
      
      $m['screening_times'][] = $arr;
      
    }
    
    return $m;

  }
  
  protected function consolidate($movies)
  {
    $m = [];
    foreach ( $movies as $movie )
    {
      if ( isset($m[$movie['title']]) )
      {
        $m[$movie['title']]['screening_times'] = array_merge($m[$movie['title']]['screening_times'], $movie['screening_times']);
        if ( isset($movie['rating']) )
        {
          $m[$movie['title']]['rating'] = $movie['rating'];
        }
      }
      else
      {
        $title = $movie['title'];
        unset($movie['title']);
        $m[$title] = $movie;
      }
    }
    return $m;
  }

}
