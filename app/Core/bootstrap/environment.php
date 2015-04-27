<?php

date_default_timezone_set('Asia/Manila');
Dotenv::load(ROOT);

if ( php_sapi_name() === 'cli' )
{
    define('SILVER_CLI_MODE', true);
}
else
{
    define('SILVER_CLI_MODE', false);
    if ( $_SERVER['SERVER_NAME'] == 'localhost' )
    {
        define('SILVER_MODE', 'dev');
    }
    else
    {
        define('SILVER_MODE', 'live');
    }
}