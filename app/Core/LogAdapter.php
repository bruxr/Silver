<?php namespace App\Core;

use Monolog\Logger;

/**
 * LogAdapter Class
 * 
 * The LogAdapter class acts as a writer for Slim's logger. It then passes
 * the messages to Monolog by calling the appropriate logging method/function.
 * 
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
class LogAdapter
{

    protected static $LEVEL_TO_METHOD = [
        \Slim\Log::EMERGENCY    => 'Emergency',
        \Slim\Log::ALERT        => 'Alert',
        \Slim\Log::CRITICAL     => 'Critical',
        \Slim\Log::ERROR        => 'Error',
        \Slim\Log::WARN         => 'Warning',
        \Slim\Log::INFO         => 'Info',
        \Slim\Log::DEBUG        => 'Debug'
    ];

    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function write($message, $level)
    {
        $method = 'add' . self::$LEVEL_TO_METHOD[$level];
        call_user_func_array(array($this->logger, $method), [$message]);
    }

}