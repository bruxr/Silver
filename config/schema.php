<?php
$this->describe('movie', [
    'title'      => 'string',
    'overview'   => 'string',
    'runtime'    => 'integer',
    'poster'     => 'string',
    'backdrop'   => 'string',
    'rating'     => 'string',
    'artists'    => 'list',
    'genres'     => 'list',
    'scores'     => 'hash',
    'status'     => 'string',
    'timestamps' => true
]);

$this->describe('screening', [
    'movie_id'  => ['as' => 'reference', 'to' => 'movie', 'indexed' => true],
    'mall_id'   => ['as' => 'reference', 'to' => 'mall', 'indexed' => true],
    'time'      => ['as' => 'datetime', 'indexed' => true],
    'format'    => 'string',
    'ticket'    => 'hash'
]);

$this->describe('mall', [
    'name'      => 'string',
    'phone'     => 'string',
    'address'   => 'string',
    'website'   => 'string',
    'latitude'  => 'string',
    'longitude' => 'string',
    'scraper'   => 'string',
    'status'    => 'string'
]);