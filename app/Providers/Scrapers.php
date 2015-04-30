<?php namespace App\Providers;

use Pimple\Container;

class Scrapers implements \Pimple\ServiceProviderInterface
{

    public function register(Container $container)
    {
        $container['App\Scrapers\Abreeza'] = function($c) {
            return new \App\Scrapers\Abreeza($c['log']);
        };

        $container['App\Scrapers\GaisanoGrand'] = function($c) {
            return new \App\Scrapers\GaisanoGrand($c['log']);
        };

        $container['App\Scrapers\GaisanoMall'] = function($c) {
            return new \App\Scrapers\GaisanoMall($c['log']);
        };

        $container['App\Scrapers\Nccc'] = function($c) {
            return new \App\Scrapers\Nccc($c['log']);
        };

        $container['App\Scrapers\SmCityDavao'] = function($c) {
            return new \App\Scrapers\SmCityDavao($c['log']);
        };

        $container['App\Scrapers\SmLanang'] = function($c) {
            return new \App\Scrapers\SmLanang($c['log']);
        };
    }

}