<?php namespace App\Core\Bus;

use Pimple\Container;
use google\appengine\api\taskqueue\PushTask;

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
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Queues a command for immediate processing.
     *
     * @param Command $command the command
     */
    public function handle(Command $command)
    {
        $this->queue[] = $command;

        $this->container['log']->addInfo(sprintf('The command "%s" has been queued for processing.', get_class($command)));

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
     * @param  Command $command the command
     * @return PushTask
     */
    public function enqueue(Command $command)
    {
        $data = $command->getData();
        $command = (new \ReflectionClass($command))->getName();
        $command = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $command));
        $task = new PushTask(sprintf('jobs/%s', $command), $data);
        $task->add();
        return $task;
    }

    /**
     * Immediately runs a command. This will also resolve any needed
     * dependencies typehinted in the command's execute method.
     *
     * @param  Command $command the command
     */
    public function execute(Command $command)
    {
        $this->container['log']->addInfo(sprintf('Preparing to execute the command "%s".', get_class($command)));

        $params = (new \ReflectionClass($command))->getMethod('execute')->getParameters();
        $p = [];
        foreach ( $params as $param )
        {
            $dep = $param->getClass()->getName();
            if ( $dep === 'Pimple\Container' )
            {
                $p[] = $this->container;
            }
            else
            {
                $p[] = $this->container[$dep];
            }
        }
        call_user_func_array(array($command, 'execute'), $p);

        $this->container['log']->addInfo(sprintf('Command "%s" successfully executed.', get_class($command)));
    }

}