<?php namespace App\Core\Bus;

abstract class Command
{

    protected $data;

    function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $data;
    }
}