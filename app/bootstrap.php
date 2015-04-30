<?php

// Load app providers
$app->register(new App\Providers\Scrapers());

// Load app routes
$routes = ['roots', 'jobs'];
foreach ( $routes as $route )
{
    require_once APP . '/Routes/' . $route .'.php';
}