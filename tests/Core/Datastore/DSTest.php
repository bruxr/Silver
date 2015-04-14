<?php

use Carbon\Carbon;
use App\Core\Datastore\Entity;

class DSTest extends TestCase
{
  
  public function setUp()
  {
    $this->mockSchema();
    $this->pushEntities();
  }
  
  public function testPutWithCustomName()
  {
    $ent2 = $this->app->services['datastore']->get(['car', 'aventador'])[0];
    
    $this->assertEquals('car', $ent2->getKind());
    $this->assertEquals('aventador', $ent2->getId());
    $this->assertContains('LP 700-4', $ent2->getProperty('models'));
    $this->assertEquals('2015-04-13 13:44:00', $ent2->getProperty('created')->format('Y-m-d H:i:s'));
    $this->assertEquals('active', $ent2->getProperty('status'));
  }
  
  public function testPutWithAutoId()
  {
    $ent = new Entity($this->app->services['datastore_schema'], 'car');
    $ent->setProperties([
      'name'      => 'Q50',
      'created'   => Carbon::parse('2015-04-14 16:04:22')
    ]);
    
    $this->app->services['datastore']->put($ent);
    $this->assertNotNull($ent->getId());
    sleep(5); // wait for the entity to get dispersed
    $this->app->services['datastore']->delete([['car', $ent->getId()]]); // Cleanup
  }
  
  public function tearDown()
  {
    $this->app->services['datastore']->delete([['car', 'aventador']]);
  }
  
  protected function mockSchema()
  {
    $field_type_map = [
      ['car', 'name', 'string'],
      ['car', 'models', 'list'],
      ['car', 'created', 'datetime'],
      ['car', 'status', 'string']
    ];
    $index_map = [
      ['car', 'name', false],
      ['car', 'models', false],
      ['car', 'created', false],
      ['car', 'status', true]
    ];
    
    $schema = $this->getMockBuilder('App\Core\Datastore\Schema')
                   ->disableOriginalConstructor()
                   ->getMock();
    $schema->method('getFieldType')
           ->will($this->returnValueMap($field_type_map));
    $schema->method('isFieldIndexed')
           ->will($this->returnValueMap($index_map));
    
    $this->app->services['datastore_schema'] = $schema;
  }
  
  protected function pushEntities()
  {
    
    // Car with custom name
    $ent = new Entity($this->app->services['datastore_schema'], ['car', 'aventador']);
    $ent->setProperties([
      'name'   => 'Aventador',
      'models' => ['LP 700-4', 'LP 700-4 Roadster', 'LP 750-4 Superveloce'],
      'created' => Carbon::parse('April 13, 2015 13:44:00'),
      'status'  => 'active'
    ]);
    
    $this->app->services['datastore']->put($ent);
    
  }
  
}