<?php
  
// Google auth scopes
$app->container->set('google_auth_scopes', function() {
    return [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/datastore'
    ];
});

// Google_Auth_AssertionCredentials
$app->container->singleton('google_auth_assertioncredentials', function($c) {
    $key = CONFIG . '/silver-key.p12';
    return new Google_Auth_AssertionCredentials(getenv('APP_SERVICE_ACCT'), $c['google_auth_scopes'], file_get_contents($key));
});

// Google_Client
$app->container->singleton('google_client', function($c) {
    $gc = new Google_Client();
    $gc->setApplicationName(getenv('APP_ID'));
    $gc->setAssertionCredentials($c['google_auth_assertioncredentials']);
    return $gc;
});

// Datastore Schema
$app->container->singleton('datastore_schema', function($c) {
    return new App\Core\Datastore\Schema(CONFIG . '/schema.php');
});

// GCD
$app->container->singleton('db_driver', function($c) {
    return new App\Core\Datastore\GCD(getenv('APP_ID'), $c['google_client'], $c['datastore_schema']);
});

// DS
$app->container->singleton('datastore', function($c) {
    return new App\Core\Datastore\Datastore($c['db_driver']);
});