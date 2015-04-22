<?php namespace App\Core;

/**
 * Observable Trait
 * 
 * Allows classes to notify observers whenever an event happens.
 * 
 * @package Silver
 * @author  Brux
 * @since   0.1.0
 */
trait Observable
{

    /**
     * Contains the class' observers.
     * 
     * @var array
     */
    protected $observers = [];

    /**
     * Registers an observer for an event.
     * 
     * @param  string           $event      event name
     * @param  Closure|array    $callback   the observer
     * @return string
     */
    public function on($event, $callback)
    {
        if ( ! is_callable($callback) )
        {
            throw new \Exception('Observer is not callable.');
        }

        $hash = $this->hashCallback($callback);
        $this->observers[$event][$hash] = $callback;
        return $hash;
    }

    /**
     * Removes a registered observer to an event.
     * 
     * @param  string        $event    event name
     * @param  Closure|array $callback exact observer that will be removed
     * @return void
     */
    public function off($event, $callback)
    {
        if ( ! is_callable($callback) )
        {
            throw new \Exception('Observer is not callable.');
        }
        $hash = $this->hashCallback($callback);
        if ( isset($this->observers[$event][$hash]) )
        {
            unset($this->observers[$event][$hash]);
        }
    }

    /**
     * Calls all observers registered to an event.
     * 
     * @param  string $event    event name
     * @param  mixed  $arg,..   arguments passed to observers
     * @return mixed
     */
    public function trigger($event, $arg = null)
    {
        if ( isset($this->observers[$event]) )
        {
            $args = func_get_args();
            if ( count($args) > 1 )
            {
                $args = array_slice($args, 1);
            }

            foreach ( $this->observers[$event] as $callback )
            {
                $arg = call_user_func_array($callback, $args);
            }
        }
        return $arg;
    }

    /**
     * Generates a string representation of an observer/callback.
     * 
     * @param  Closure|array    $callback   the callback
     * @return string
     */
    protected function hashCallback($callback)
    {
        if ( $callback instanceof Closure )
        {
            return spl_object_hash($callback);
        }
        elseif ( is_array($callback) )
        {
            if ( count($callback) == 2 )
            {
                return spl_object_hash($callback[0]) .'::'. $callback[1];
            }
            else
            {
                return $callback[1];
            }
        }
    }

}