<?php namespace App\Exceptions;

class ParseException extends \Exception
{

  function __construct($scraper, $message = '', $invalid_str = null, $code = 0, Exception $previous = null)
  {
    $message = trim($message);
    if ( $invalid_str )
    {
      $message = sprintf('[%s] %s (%s)', $scraper, $message, (string) $invalid_str);
    }
    else
    {
      $message = sprintf('[%s] %s', $scraper, $message);
    }
    parent::__construct($message, $code, $previous);
  }

}