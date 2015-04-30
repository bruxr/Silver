<?php namespace App\Core\Providers;

use App\Core\Bus\Dispatcher;
use Pimple\Container;

class Bus implements \Pimple\ServiceProviderInterface
{

    public function register(Container $container)
    {
        $container['bus'] =
        $container['App\Core\Bus\Dispatcher'] = function($c) {
            return new Dispatcher($c);
        };
    }

}