<?php
  
// Google auth scopes
$app->container->set('google_auth_scopes', function() {
    return [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/datastore'
    ];
});

// Google_Auth_AssertionCredentials
$app->container->singleton('Google_Auth_AssertionCredentials', function($c) {
    $key = CONFIG . '/silver-key.p12';
    return new Google_Auth_AssertionCredentials(getenv('APP_SERVICE_ACCT'), $c['google_auth_scopes'], file_get_contents($key));
});

// Google_Client
$app->container->singleton('Google_Client', function($c) {
    $gc = new Google_Client();
    $gc->setApplicationName(getenv('APP_ID'));
    $gc->setAssertionCredentials($c['Google_Auth_AssertionCredentials']);
    return $gc;
});

// Datastore Schema
$app->container->singleton('App\Core\Datastore\Schema', function($c) {
    return new App\Core\Datastore\Schema(CONFIG . '/schema.php');
});

// GCD
$app->container->singleton('App\Core\Datastore\Drivers\GCD', function($c) {
    return new App\Core\Datastore\Drivers\GCD(getenv('APP_ID'), $c['google_client'], $c['datastore_schema']);
});

// DS
$app->container->singleton('App\Core\Datastore\Datastore', function($c) {
    return new App\Core\Datastore\Datastore($c['datastore_driver']);
});