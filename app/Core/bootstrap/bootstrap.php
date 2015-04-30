<?php

use App\Core\Middleware;

define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
define('APP', ROOT . '/app');
define('CORE', APP . '/Core');
define('CONFIG', ROOT . '/config');

require_once ROOT . '/vendor/autoload.php';

$app = new App\Core\SilverApp();
$app->add(new Middleware\LazyRoutes());

require_once CORE . '/bootstrap/services.php';
require_once APP . '/Routes/roots.php';