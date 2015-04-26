<?php

use App\Core\Middleware;

define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
define('APP', ROOT . '/app');
define('CORE', APP . '/Core');
define('CONFIG', ROOT . '/config');

require_once ROOT . '/vendor/autoload.php';

$app = new \Slim\Slim();

require_once CORE . '/bootstrap/environment.php';
require_once CORE . '/bootstrap/services.php';

$app->get('/', function() {
    echo 'hello world!';
});

// Run the app if we aren't in CLI mode.
if ( php_sapi_name() !== 'cli' )
{
    $app->run();
}
