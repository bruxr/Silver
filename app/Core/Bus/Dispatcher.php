<?php namespace App\Core;

/**
 * Command Bus Dispatcher
 * 
 * This class is responsible for executing commands synchronously or
 * asynchronously through App Engine's Task Queues.
 * 
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
class Dispatcher
{
    
    /**
     * Internal flag used to detect if the class at that time
     * processing a command.
     * 
     * @var boolean
     */
    protected $isProcessing = false;

    /**
     * Command queue.
     * 
     * @var array
     */
    protected $queue = [];

    /**
     * The app container for easily resolving any dependencies.
     * 
     * @var object
     */
    protected $container;

    /**
     * Constructor.
     * 
     * @param object $container service container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Queues a command for immediate processing.
     * 
     * @param CommandInterface $command the command
     */
    public function handle(CommandInterface $command)
    {
        $this->queue[] = $comand;
        
        if ( ! $this->isProcessing )
        {
            $this->isProcessing = true;
            
            while ( $command = array_shift($this->queue) )
            {
                $this->execute($command);
            }

            $this->isProcessing = false;
        }
    }

    /**
     * Queues a command for processing later through
     * App Engine's Task queues.
     * 
     * @param  CommandInterface $command the command
     */
    public function enqueue(CommandInterface $command)
    {
        $job = (new \ReflectionClass($command))->getShortName();
        $job = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $job));
        $task = new PushTask(sprintf('jobs/%s', $job), $args);
        $task->add();
    }

    /**
     * Immediately runs a command. This will also resolve any needed
     * dependencies typehinted in the command's execute method.
     * 
     * @param  CommandInterface $command the command
     */
    public function execute(CommandInterface $command)
    {
        $params = (new \ReflectionClass($command))->getMethod('execute')->getParameters();
        $p = [];
        foreach ( $params as $param )
        {
            $p[] = $this->container[$param->getClass()];
        }
        call_user_func_array(array($command, 'execute'), $p);
    }

}