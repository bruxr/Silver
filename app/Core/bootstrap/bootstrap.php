<?php

define('ROOT', dirname(dirname(__FILE__)));
define('APP', ROOT . '/app');

require_once ROOT . '/vendor/autoload.php';

date_default_timezone_set('Asia/Manila');

Dotenv::load(ROOT);