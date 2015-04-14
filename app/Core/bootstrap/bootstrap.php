<?php

use App\Middleware\Environment;
use App\Services\Log;
use Auryn\Provider;
use Auryn\ReflectionPool;
use Slim\Slim;
use Symfony\Component\Yaml;

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
$app->add(new Environment());

// Setup our DI
$app->services = new Provider(new ReflectionPool());

// Setup datastore
$yaml = new Yaml\Parser;

// Setup the Database
//$yaml = new Parser();
//dump($yaml->parse(file_get_contents(APP . '/schema.yml')));
//DS::initialize($yaml->parse(file_get_contents(APP . '/schema.yml')));

$app->run();