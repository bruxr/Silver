<?php

$app->get('/', function($req, $resp, $args) {
    $resp->write('hello world!');
});