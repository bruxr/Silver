<?php namespace App\Core\Providers;

use Pimple\Container;

class Datastore implements \Pimple\ServiceProviderInterface
{

    public function register(Container $container)
    {
        // Datastore Schema
        $container['App\Core\Datastore\Schema'] = function($c) {
            return new App\Core\Datastore\Schema($container['schema_path']);
        };

        // GCD
        $container['App\Core\Datastore\Drivers\GCD'] = function($c) {
            return new App\Core\Datastore\Drivers\GCD(getenv('APP_ID'), $c['google_client'], $c['datastore_schema']);
        };

        // Datastore
        $container['App\Core\Datastore\Datastore'] = function($c) {
            return new App\Core\Datastore\Datastore($c[$c['datastore_driver']]);
        };
    }
    
}