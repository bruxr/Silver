<?php namespace App\Core\Providers;

use Pimple\Container;

class Google implements \Pimple\ServiceProviderInterface
{

    public function register(Container $container)
    {
        // Google auth scopes
        $container['google_auth_scopes'] = $container->protect(function() {
            return [
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/datastore'
            ];
        });

        // Google_Auth_AssertionCredentials
        $container['Google_Auth_AssertionCredentials'] = function($c) {
            $key = CONFIG . '/silver-key.p12';
            return new Google_Auth_AssertionCredentials(getenv('APP_SERVICE_ACCT'), $c['google_auth_scopes'], file_get_contents($key));
        };

        // Google_Client
        $container['Google_Client'] = function($c) {
            $gc = new Google_Client();
            $gc->setApplicationName(getenv('APP_ID'));
            $gc->setAssertionCredentials($c['Google_Auth_AssertionCredentials']);
            return $gc;
        };
    }
    
}