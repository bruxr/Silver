<?php

use Slim\Slim;
use App\Middleware\Environment;
use App\Middleware\Services;
use App\Services\Keys;
use App\Services\Log;

$app = new Slim(array(
  'log.writer'          => Log::instance(),
  'cookies.secure'      => true,
  'cookies.httponly'    => true,
  'cookies.secret_key'  => Keys::get('cookies_secret')
));

$app->add(new Environment());
$app->add(new Services());

$app->run();