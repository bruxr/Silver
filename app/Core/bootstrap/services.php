<?php
  
// Google auth scopes
$app['google_auth_scopes'] = function() {
    return [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/datastore'
    ];
};

// Google_Auth_AssertionCredentials
$app['google_auth_assertioncredentials'] = function($c) {
    $key = CONFIG . '/silver-key.p12';
    return new Google_Auth_AssertionCredentials(getenv('APP_SERVICE_ACCT'), $c['google_auth_scopes'], file_get_contents($key));
};

// Google_Client
$app['google_client'] = function($c) {
    $gc = new Google_Client();
    $gc->setApplicationName(getenv('APP_ID'));
    $gc->setAssertionCredentials($c['google_auth_assertioncredentials']);
    return $gc;
};

// Datastore Schema
$app['datastore_schema'] = function($c) {
    return new App\Core\Datastore\Schema(CONFIG . '/schema.php');
};

// GCD
$app['datastore_driver'] = function($c) {
    return new App\Core\Datastore\GCD(getenv('APP_ID'), $c['google_client'], $c['datastore_schema']);
};

// DS
$app['datastore'] = function($c) {
    return new App\Core\Datastore\Datastore($c['datastore_driver']);
};

// Logger
$app['logger'] = function($c) {
    $log = new Monolog\Logger('silver');
    if ( SILVER_MODE == 'dev' )
    {
        $log->pushHandler(new Monolog\Handler\RotatingFileHandler(ROOT . '/storage/logs/silver'));
    }
    else
    {
        $log->pushHandler(new Monolog\Handler\SyslogHandler('app'));
    }
    return $log;
};
$app['logger']->addInfo('ready.');