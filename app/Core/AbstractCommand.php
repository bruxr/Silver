<?php namespace App\Core;

abstract class AbstractCommand
{

    protected $data;

    function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function execute();

    public function getData()
    {
        return $data;
    }
}