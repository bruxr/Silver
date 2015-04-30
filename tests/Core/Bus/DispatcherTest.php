<?php 
use App\Core\Bus\Command;
use App\Core\Bus\Dispatcher;

class DispatcherTest extends TestCase
{

    public function testDispatch()
    {
        $container = new Pimple\Container();
        $container['Monolog\Logger'] = function() {
            return new Monolog\Logger('test');
        };

        $GLOBALS['test_var'] = 1;
        $bus = new Dispatcher($container);
        $bus->handle(new TestCommand());

        $this->assertEquals(2, $GLOBALS['test_var']);
    }

}

class TestCommand extends Command
{
    
    public function execute(Monolog\Logger $log)
    {
        $GLOBALS['test_var']++;
    }

}