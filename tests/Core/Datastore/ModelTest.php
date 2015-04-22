<?php

class Car extends App\Core\Datastore\Model { }

class ModelTest extends TestCase
{

    public function testBasicInstantiation()
    {
        $m = new Car();
        $this->assertInstanceOf('App\Core\Datastore\Model', $m);
    }

    public function testGetSet()
    {
        $m = new Car();
        $m->name = 'R8';
        $m->set('manufacturer', 'Audi');
        $this->assertEquals('R8', $m->name);
        $this->assertEquals('Audi', $m->get('manufacturer'));
    }

    public function testIsset()
    {
        $m = new Car();
        $m->name = 'Skyline GTR';
        $this->assertTrue(isset($m->name));
        $this->assertTrue($m->has('name'));
        $this->assertFalse(isset($m->manufacturer));
        $this->assertFalse($m->has('manufacturer'));
    }

    public function testPropsAsArray()
    {
        $m = new Car();
        $m->name = 'Supra';
        $m->manufacturer = 'Toyota';
        $p = $m->getProperties();
        $this->assertEquals('Supra', $p['name']);
        $this->assertEquals('Toyota', $p['manufacturer']);
    }

    public function testIsNew()
    {
        $m = new Car();
        $this->assertTrue($m->isNew());
    }

    public function testIsDirty()
    {
        $m = new Car();
        $m->name = 'Civic Si';
        $this->assertTrue($m->isDirty());
        $this->assertTrue($m->isDirty('name'));
        $this->assertEquals('Civic Si', $m->getDirtyProperties()['name']);
    }

    public function testRefresh()
    {
        $car = new Car();
        $car->hydrate(['id' => 981, 'name' => 'Integra', 'unlocked' => false]);
        $this->assertFalse($car->unlocked);
        $car->unlocked = true;
        $this->assertTrue($car->unlocked);
        $car->refresh();
        $this->assertFalse($car->unlocked);
    }

}