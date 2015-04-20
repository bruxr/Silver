<?php

use App\Middleware\Environment;
use App\Services\Log;
use Slim\Slim;

define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
define('APP', ROOT . '/app');
define('CORE', APP . '/Core');
define('CONFIG', ROOT . '/config');

require_once ROOT . '/vendor/autoload.php';
require_once CORE . '/bootstrap/environment.php';

// Setup our slim app
$app = new Slim(array(
  'log.writer'          => Log::instance(),
  'cookies.secure'      => true,
  'cookies.httponly'    => true,
  'cookies.secret_key'  => getenv('COOKIE_SECRET')
));

// Setup our services
require_once CORE . '/bootstrap/services.php';