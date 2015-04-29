<?php

define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
define('APP', ROOT . '/app');
define('CORE', APP . '/Core');
define('CONFIG', ROOT . '/config');

require_once ROOT . '/vendor/autoload.php';

$app = new \Slim\Slim();

require_once CORE . '/bootstrap/environment.php';
require_once CORE . '/bootstrap/services.php';

// Do app config based on the environment
require_once CORE . '/bootstrap/environment/' . SILVER_MODE . '.php';

$app->get('/', function() {
    echo 'hello world!';
});

// Run the app if we aren't in CLI mode.
if ( ! SILVER_CLI_MODE )
{
    $app->run();
}
