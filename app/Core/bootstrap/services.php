<?php

use App\Core\Datastore;
use Symfony\Component\Yaml;

return function($services) {
  
  $scopes = [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/datastore'
  ];
  $key = CONFIG . '/silver-key.p12';
  
  // Setup Google client
  $gc = new Google_Client();
  $gc->setApplicationName(getenv('APP_ID'));
  if ( file_exists($key) )
  {
    $c = new Google_Auth_AssertionCredentials(getenv('APP_SERVICE_ACCT'), $scopes, file_get_contents($key));
    $gc->setAssertionCredentials($c);
  }
  $services->share($gc);

  // Setup datastore
  $services->share(new Datastore\Schema(new Yaml\Parser, CONFIG . '/schema.yml'));
  $services->share(new Datastore\DS($services->make('App\Core\Datastore\Schema'), $services->make('Google_Client'), getenv('APP_ID')));
  
};