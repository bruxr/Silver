<?php namespace App\Core\Providers;

use Pimple\Container;

class Log implements \Pimple\ServiceProviderInterface
{

    public function register(Container $container)
    {
        $container['log'] = function() {
            $log = new \Monolog\Logger('silver');
            if ( SILVER_MODE == 'development' )
            {
                $log->pushHandler(new \Monolog\Handler\RotatingFileHandler($container['log_path']));
            }
            else
            {
                $log->pushHandler(new \Monolog\Handler\SyslogHandler('app'));
            }
            return $log;
        };
    }

}