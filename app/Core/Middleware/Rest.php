<?php namespace App\Core\Middleware;

class Rest extends \Slim\Middleware
{
    public function call()
    {
        $this->detectFormat();
        $this->setupViews();
        $this->setupErrors();
        $this->next->call();
    }

    protected function detectFormat()
    {
        $uri = $this->app->request->getResourceUri();
        if ( preg_match('/\.json$/', $uri) )
        {
            $this->app->environment['format'] = 'json';
        }
        else
        {
            $this->app->environment['format'] = 'html';
        }
    }

    public function setupViews()
    {
        # code...
    }

    protected function setupErrors()
    {
        
    }
}