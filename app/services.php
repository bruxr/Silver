<?php
// -----------------------------------------------------------------------------
// SCRAPERS
// -----------------------------------------------------------------------------

$container->singleton('App\Scrapers\Abreeza', function($c) {
    return new App\Scrapers\Abreeza($c['Monolog\Logger']);
});

$container->singleton('App\Scrapers\GaisanoGrand', function($c) {
    return new App\Scrapers\GaisanoGrand($c['Monolog\Logger']);
});

$container->singleton('App\Scrapers\GaisanoMall', function($c) {
    return new App\Scrapers\GaisanoMall($c['Monolog\Logger']);
});

$container->singleton('App\Scrapers\Nccc', function($c) {
    return new App\Scrapers\Nccc($c['Monolog\Logger']);
});

$container->singleton('App\Scrapers\SmCityDavao', function($c) {
    return new App\Scrapers\SmCityDavao($c['Monolog\Logger']);
});

$container->singleton('App\Scrapers\SmLanang', function($c) {
    return new App\Scrapers\SmLanang($c['Monolog\Logger']);
});