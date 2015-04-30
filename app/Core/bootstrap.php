<?php

use App\Core\Providers;

// Define paths
define('ROOT', dirname(dirname(dirname(__FILE__))));
define('APP', ROOT . '/app');
define('CORE', APP . '/Core');
define('CONFIG', ROOT . '/config');

// Load composer's autoloader
require_once ROOT . '/vendor/autoload.php';

// Setup environment
date_default_timezone_set('Asia/Manila');
Dotenv::load(ROOT);

// App Mode
if ( $_SERVER['SERVER_NAME'] == 'localhost' )
{
    define('SILVER_MODE', 'development');
}
else
{
    define('SILVER_MODE', 'production');
}

// Initialize our app
$app = new \Slim\App();
$app['log_path'] = ROOT . 'storage/logs/silver';
$app['schema_path'] = CONFIG . '/schema.php';
$app['datastore_driver'] = 'App\Core\Datastore\Drivers\GCD';

// Add our core service providers
$app->register(new Providers\Log());
$app->register(new Providers\Bus());
$app->register(new Providers\Google());
$app->register(new Providers\Datastore());

require_once APP . '/Routes/roots.php';