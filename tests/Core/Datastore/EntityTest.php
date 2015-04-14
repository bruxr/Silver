<?php

use Carbon\Carbon;
use App\Core\Datastore\Entity;

class EntityTest extends TestCase
{
  
  public function setUp()
  {
    $schema = $this->getMockBuilder('App\Core\Datastore\Schema')
                   ->disableOriginalConstructor()
                   ->getMock();
    
    $field_type_map = [
      ['car', 'name', 'string'],
      ['car', 'models', 'list'],
      ['car', 'created', 'datetime']
    ];
    $schema->method('getFieldType')
           ->will($this->returnValueMap($field_type_map));
    
    $field_index_map = [
      ['car', 'name', false],
      ['car', 'models', false],
      ['car', 'created', false]
    ];
    $schema->method('isFieldIndexed')
           ->will($this->returnValueMap($field_index_map));
    
    $this->app->services['datastore_schema'] = $schema;
  }
  
  public function testBasicInstantiation()
  {
    
    $created = '2015-04-13 22:36:50';
    $ent = new Entity($this->app->services['datastore_schema'], 'car');
    $ent->setProperty('name', 'R8');
    $ent->setProperty('models', ['V8', 'V10', 'V10+']);
    $ent->setProperty('created', Carbon::parse($created));
    
    $this->assertEquals('car', $ent->getKind());
    $this->assertEquals('R8', $ent->getProperty('name'));
    $this->assertContains('V10+', $ent->getProperty('models'));
    $this->assertEquals($created, $ent->getProperty('created')->format('Y-m-d H:i:s'));
  }
  
  public function testObjectInstantiation()
  {
    $created = '2015-04-13 22:36:50';
    $d = Carbon::parse($created);
    $d->tz = 'UTC';
    
    $e = new Google_Service_Datastore_Entity();
    $kpe = new Google_Service_Datastore_KeyPathElement();
    $kpe->setKind('car');
    $k = new Google_Service_Datastore_Key();
    $k->setPath([$kpe]);
    $e->setKey($k);
    
    $props = [];
    $p = new Google_Service_Datastore_Property();
    $p->setStringValue('R8');
    $props['name'] = $p;
    $p = new Google_Service_Datastore_Property();
    $p->setStringValue(json_encode(['V8', 'V10', 'V10+']));
    $props['models'] = $p;
    $p = new Google_Service_Datastore_Property();
    $p->setDateTimeValue($d->format('Y-m-d\TH:i:s.000\Z'));
    $props['created'] = $p;
    $e->setProperties($props);

    $ent = new Entity($this->app->services['datastore_schema'], $e);
    
    $this->assertEquals('car', $ent->getKind());
    $this->assertEquals('R8', $ent->getProperty('name'));
    $this->assertContains('V10', $ent->getProperty('models'));
    $this->assertEquals($created, $ent->getProperty('created')->format('Y-m-d H:i:s'));
  }
  
}