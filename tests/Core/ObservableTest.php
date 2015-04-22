<?php
use App\Core\Observable;

class ObservableTest extends TestCase
{

    public function testTrigger()
    {
        $this->called_1 =
        $this->called_2 = false;

        $x = new Subject();
        $x->on('test_event', function() {
            $this->called_1 = true;
        });
        $x->on('test_event', array($this, 'setCalled2'));
        $x->trigger('test_event');

        $this->assertTrue($this->called_1);
        $this->assertTrue($this->called_2);
    }

    public function testTriggerWithArgs()
    {
        $x = new Subject();
        $x->on('modify', function($var1, $var2) {
            return $var1 .' '. $var2;
        });
        $var = $x->trigger('modify', 'Hello', 'World');
        $this->assertEquals('Hello World', $var);
    }

    public function testOff()
    {
        $this->called_3 = false;
        $x = new Subject();
        $x->on('test_event', function() {
            $this->called_3 = true;
        });
        $x->off('test_event', function() {
            $this->called_3 = true;
        });
        $x->trigger('test_event');
        $this->assertFalse($this->called_3);
    }

    public function setCalled2()
    {
        $this->called_2 = true;
    }

}

class Subject
{
    use Observable;
}