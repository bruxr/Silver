<?php

use Carbon\Carbon;

class Person extends App\Core\Datastore\Model
{
    public function getCars()
    {
        return $this->hasMany('Car');
    }
}

class Car extends App\Core\Datastore\Model
{
    public function getOwner()
    {
        return $this->belongsTo('Person', ['foreign_key' => 'owner_id']);
    }
}

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

    public function testBelongsTo()
    {
        $ds = $this->getMockBuilder('App\Core\Datastore\Datastore')
                   ->disableOriginalConstructor()
                   ->getMock();
        $ds->method('find')
            ->willReturn(new Person(['name' => 'John Doe']));
        $car = new Car([], $ds);
        $car->name = 'Focus';
        $car->owner_id = 123;
        $this->assertInstanceOf('Person', $car->getOwner());
    }

    public function testHasMany()
    {
        $ds = $this->getMockBuilder('App\Core\Datastore\Datastore')
                   ->disableOriginalConstructor()
                   ->getMock();
        $ds->method('findCustom')
           ->willReturn([new Car(['name' => 'R8']), new Car(['name' => 'M3'])]);
        $guy = new Person([], $ds);
        $guy->id = 22;
        $guy->name = 'John Doe';
        $this->assertContainsOnly('Car', $guy->getCars());
    }

    public function testJsonEncode()
    {
        $car = new Car();
        $car->name = 'Skyline';
        $car->price = 3000000;
        $car->purchased = Carbon::parse('2015-04-22');
        $json = json_encode($car);
        $this->assertEquals('{"name":"Skyline","price":3000000,"purchased":"2015-04-22T00:00:00+0800"}', $json);
    }

}