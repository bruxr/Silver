<?php
/**
 * Jobs Route
 * 
 * This route is responsible for dispatching jobs sent through
 * the jobs endpoint.
 * 
 * @author Brux
 * @since  0.1.0
 */
$app->get('/jobs/{command:[a-z\-]+}', function($req, $resp, $args) {
    $command_class = ucwords(mb_ereg_replace('-', ' ', $args['command']));
    $command_class = mb_ereg_replace(' ', '', $command_class);
    $command_class = 'App\Commands\\' . $command_class;
    if ( class_exists($command_class) )
    {
        $command = new $command_class($req->post());
        $this['bus']->handle($command);
    }
    else
    {
        return $this['notFoundHandler']($req, $resp);
    }
});