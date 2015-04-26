<?php

date_default_timezone_set('Asia/Manila');
Dotenv::load(ROOT);

if ( php_sapi_name() !== 'cli' )
{
    if ( $_SERVER['SERVER_NAME'] == 'localhost' )
    {
        define('SILVER_MODE', 'dev');
    }
    else
    {
        define('SILVER_MODE', 'live');
    }
}