<?php

namespace App\Services;
  
use Monolog\Logger;
use Monolog\Handler\SyslogHandler;
use Slim\Log as LogLevel;

/**
 * Logger
 *
 * A simple wrapper over the very powerful Monolog library. This configures
 * Monolog to use syslog() which is the function App Engine recommends
 * for logging.
 * 
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
class Log
{
  
  /**
   * Our only instance.
   *
   * @var Log
   */
  protected static $instance = null;
  
  /**
   * Monolog instance.
   *
   * @var Monolog
   */
  protected $monolog;
  
  /**
   * Constructor. Setups Monolog.
   */
  protected function __construct()
  {
    $this->monolog = new Logger('silver');
    $this->monolog->pushHandler(new SyslogHandler('intranet'));
  }
  
  /**
   * Returns the instance of logger.
   *
   * @return Log
   */
  public static function instance()
  {
    if ( is_null(self::$instance) )
    {
      self::$instance = new self;
    }
    return self::$instance;
  }
  
  public static function emergency($message)
  {
    self::instance()->getMonolog()->addEmergency($message);
  }
  
  public static function alert($message)
  {
    self::instance()->getMonolog()->addAlert($message);
  }
  
  public static function critical($message)
  {
    self::instance()->getMonolog()->addCritical($message);
  }
  
  public static function error($message)
  {
    self::instance()->getMonolog()->addError($message);
  }
  
  public static function warn($message)
  {
    self::instance()->getMonolog()->addWarning($message);
  }
  
  public static function notice($message)
  {
    self::instance()->getMonolog()->addNotice($message);
  }
  
  public static function info($message)
  {
    self::instance()->getMonolog()->addInfo($message);
  }
  
  public static function debug($message)
  {
    self::instance()->getMonolog()->addDebug($message);
  }
  
  /**
   * Writes a message to the logger. Slim requires this method
   * to be available as this will be called when writing to logs.
   *
   * @param string $message message
   * @param string $level log level
   * @return void
   */
  public function write($message, $level)
  {
    
    $message = (string) $message;
    
    switch ( $level )
    {
      
      case LogLevel::EMERGENCY:
        $this->monolog->addEmergency($message);
        break;
        
      case LogLevel::ALERT:
        $this->monolog->addAlert($message);
        break;
        
      case LogLevel::CRITICAL:
        $this->monolog->addCritical($message);
        break;
        
      case LogLevel::ERROR:
        $this->monolog->addError($message);
        break;
        
      case LogLevel::WARN:
        $this->monolog->addWarning($message);
        break;
        
      case LogLevel::NOTICE:
        $this->monolog->addNotice($message);
        break;
        
      case LogLevel::INFO:
        $this->monolog->addInfo($message);
        break;
        
      case LogLevel::DEBUG:
        $this->monolog->addDebug($message);
        break;
          
    }
    
  }
  
  /**
   * Returns our instance of Monolog.
   *
   * @return Monolog
   */
  public function getMonolog()
  {
    return $this->monolog;
  }
  
}