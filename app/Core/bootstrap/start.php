<?php

use Slim\Slim;
use App\Middleware\Environment;
use App\Middleware\Services;
use App\Services\Log;

use App\Core\Datastore\DS;
use Symfony\Component\Yaml\Parser;

// Setup the Database
$yaml = new Parser();
dump($yaml->parse(file_get_contents(APP . '/schema.yml')));
//DS::initialize($yaml->parse(file_get_contents(APP . '/schema.yml')));

$app = new Slim(array(
  'log.writer'          => Log::instance(),
  'cookies.secure'      => true,
  'cookies.httponly'    => true,
  'cookies.secret_key'  => getenv('COOKIE_SECRET')
));

$app->add(new Environment());
$app->add(new Services());

$app->run();