<?php namespace App\Core;

class MorphingView extends \Slim\View
{
    
    public function render($template)
    {
        dump($template);
    }

}