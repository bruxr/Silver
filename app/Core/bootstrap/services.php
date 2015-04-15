<?php

return function($box)
{
  
  // Google auth scopes
  $box['google_auth_scopes'] = [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/datastore'
  ];
  
  // Google_Auth_AssertionCredentials
  $box['google_auth_assertioncredentials'] = function($b) {
    $key = CONFIG . '/silver-key.p12';
    return new Google_Auth_AssertionCredentials(getenv('APP_SERVICE_ACCT'), $b['google_auth_scopes'], file_get_contents($key));
  };
  
  // Google_Client
  $box['google_client'] = function($b) {
    $gc = new Google_Client();
    $gc->setApplicationName(getenv('APP_ID'));
    $gc->setAssertionCredentials($b['google_auth_assertioncredentials']);
    return $gc;
  };
  
  // Yaml Parser
  $box['yaml_parser'] = function($b) {
    return new Symfony\Component\Yaml\Parser();
  };
  
  // Datastore Schema
  $box['datastore_schema'] = function($b) {
    return new App\Core\Datastore\Schema($b['yaml_parser'], CONFIG . '/schema.yml');
  };
  
  // DS
  $box['datastore'] = function($b) {
    return new App\Core\Datastore\DS($b['datastore_schema'], $b['google_client'], getenv('APP_ID'));
  };
  
};