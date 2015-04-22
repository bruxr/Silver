<?php

use App\Core\Datastore\Schema;

class SchemaTest extends TestCase
{
  
  public function setUp()
  {
    $schema = [
      'car' => [
        'name'    => 'string',
        'slug'    => [
          'type'    => 'string',
          'indexed' => true
        ],
        'models' => 'list',
        'updated' => 'datetime',
        'created' => 'datetime'
      ]
    ];
    
    $this->schema = new Schema();
    $this->schema->describe('car', [
      'slug'       => ['as' => 'string', 'indexed' => true],
      'models'     => 'list',
      'timestamps' => true
    ]);
    $this->schema->describe('person', [
      'name'      => 'string'
    ]);
  }
  
  public function testGetFieldType()
  {
    $this->assertEquals('datetime', $this->schema->getFieldType('car', 'updated'));
  }
  
  public function testGetFieldTypeWithUnknownField()
  {
    $this->assertNull($this->schema->getFieldType('car', 'missing'));
  }
  
  public function testIsFieldIndexed()
  {
    $this->assertTrue($this->schema->isFieldIndexed('car', 'slug'));
  }
  
  public function testIsFieldIndexedOnNonIndex()
  {
    $this->assertFalse($this->schema->isFieldIndexed('car', 'models'));
  }
  
  public function testIsFieldIndexedWithUnknownField()
  {
    $this->assertFalse($this->schema->isFieldIndexed('car', 'missing'));
  }
  
  public function testHasTimestamps()
  {
    $this->assertTrue($this->schema->hasTimestamps('car'));
    $this->assertFalse($this->schema->hasTimestamps('person'));
  }
  
}