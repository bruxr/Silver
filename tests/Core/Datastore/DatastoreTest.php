<?php

namespace App\Models { class Food extends \App\Core\Datastore\Model { } }

namespace {
  
use Carbon\Carbon;
use App\Core\Datastore\Datastore;

class DatastoreTest extends TestCase
{
  
  public function before()
  {
    $driver = $this->getMockBuilder('App\Core\Datastore\Drivers\GCD')
                   ->disableOriginalConstructor()
                   ->getMock();
    $find_map = [
      [
        'SELECT * FROM food WHERE id = :id LIMIT 1',
        [':id' => 'chicken-bbq'],
        [[
          'id' => 'chicken-bbq',
          'name' => 'Chicken Barbeque',
          'ingredients' => ['Chicken', 'Charcoal', 'Grill'],
          'created' => Carbon::parse('2015-04-15T15:22:23-00:00')
        ]]
      ],
      [
        'SELECT * FROM food',
        [],
        [
          [
            'id' => 'kare-kare',
            'name' => 'Kare-kare'
          ],
          [
            'id' => 'lechon',
            'name' => 'Lechon'
          ]
        ]
      ]
    ];
    $driver->method('find')
           ->will($this->returnValueMap($find_map));
    
    $driver->method('create')
           ->will($this->returnValueMap([
             ['food', ['name' => 'Lechon'], ['id' => 223, 'type' => 'Lechon']]
            ]));
    
    $this->ds = new Datastore($driver);
  }

  public function testFind()
  {
    $r = $this->ds->find('food', 'chicken-bbq');
    $this->assertInstanceOf('\App\Models\Food', $r);
    $r2 = $this->ds->find('food', 'chicken-bbq');
    $this->assertSame($r, $r2);
  }

  public function testFindMany()
  {
    $r = $this->ds->findAll('food');
    $this->assertContainsOnlyInstancesOf('App\Models\Food', $r);
  }
  
  public function testCreate()
  {
    $r = $this->ds->create('food', ['name' => 'bulad']);
    $this->assertInstanceOf('\App\Models\Food', $r);
  }
  
  public function testPut()
  {
    $lechon = $this->ds->create('food', ['name' => 'Lechon']);
    $this->ds->put($lechon);
    $lechon2 = $this->ds->find('food', 223);
    $this->assertSame($lechon, $lechon2);
  }
  
}

} // end global namespace