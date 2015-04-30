<?php namespace App\Core\Middleware;

class LazyRoutes extends \Slim\Middleware
{
    public function call()
    {
        $app = $this->app;
        $app->hook('slim.before', function() use ($app) {
            $req = trim($app->request->getResourceUri(), '/');
            $req_parts = split('/', $req);
            $file = $req_parts[0];
            if ( mb_ereg_match('^[a-z]+$', $file) )
            {
                $path = sprintf('%s/routes/%s.php', APP, $file);
                if ( file_exists($path) )
                {
                    $app->logger->addInfo(sprintf('Route file "%s.php" loaded for request "%s".', $file, $req));
                    require_once $path;
                }
            }
        }, 1);
        $this->next->call();
    }
}