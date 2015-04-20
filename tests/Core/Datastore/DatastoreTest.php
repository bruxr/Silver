<?php

namespace App\Models { class Food extends \App\Core\Datastore\Model { } }

namespace {
  
use App\Core\Datastore\Datastore;

class DSTest extends TestCase
{
  
  public function before()
  {
    $driver = $this->getMockBuilder('App\Core\Datastore\GCD')
                   ->disableOriginalConstructor()
                   ->getMock();
    $find_map = [
      [
        'SELECT * FROM food WHERE id = :id',
        [':id' => 'chicken-bbq'],
        [[
          'id' => 'chicken-bbq',
          'ingredients' => '["Chicken","Charcoal","Grill"]',
          'name' => 'Chicken Barbeque',
          'created' => '2015-04-15 15:22:23'
        ]]
      ],
      [
        'SELECT * FROM food WHERE edible = :edible',
        [':edible' => true],
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
             ['food', ['name' => 'lechon'], ['id' => 223, 'type' => 'lechon']]
            ]));
    
    $this->ds = new Datastore($driver);
  }
  
  /*
  public function testFindById()
  {
    $r = $this->ds->find('food', 'chicken-bbq');
    $this->assertInstanceOf('\App\Models\Food', $r);
    $r2 = $this->ds->find('food', 'chicken-bbq');
    $this->assertSame($r, $r2);
  }
  
  public function testFindMany()
  {
    $r = $this->ds->find('food')->where('edible', true)->get();
    $this->assertContainsOnlyInstancesOf('App\Models\Food', $r);
  }
  
  public function testCreate()
  {
    $r = $this->ds->create('food', ['name' => 'bulad']);
    $this->assertInstanceOf('\App\Models\Food', $r);
  }
  
  public function testPut()
  {
    $r = $this->ds->put($this->ds->create('food', ['name' => 'lechon']));
    $this->assertInstanceOf('App\Models\Food', $r);
    $this->assertEquals(223, $r->id);
  }
  */
  
}

} // end global namespace