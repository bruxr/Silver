<?php

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
    $parser = $this->getMockBuilder('Symfony\Component\Yaml\Parser')->getMock();
    $parser->method('parse')
           ->willReturn($schema);
    
    $this->schema = new App\Core\Datastore\Schema($parser, ROOT . '/config/schema.yml');
  }
  
  public function testGetFieldType()
  {
    $this->assertEquals('datetime', $this->schema->getFieldType('car', 'updated'));
    $this->assertEquals('datetime', $this->schema->getFieldType('car:updated'));
  }
  
  public function testGetFieldTypeWithUnknownField()
  {
    $this->assertNull($this->schema->getFieldType('car', 'missing'));
  }
  
  public function testIsFieldIndexed()
  {
    $this->assertTrue($this->schema->isFieldIndexed('car', 'slug'));
    $this->assertTrue($this->schema->isFieldIndexed('car:slug'));
  }
  
  public function testIsFieldIndexedOnNonIndex()
  {
    $this->assertFalse($this->schema->isFieldIndexed('car', 'models'));
    $this->assertFalse($this->schema->isFieldIndexed('car:models'));
  }
  
  public function testIsFieldIndexedWithUnknownField()
  {
    $this->assertFalse($this->schema->isFieldIndexed('car', 'missing'));
  }
  
  public function testSetSchema()
  {
    $this->schema->setSchema('supercar', ['name' => 'string', 'carbon_fiber' => ['type' => 'boolean', 'indexed' => true]]);
    $this->assertEquals('string', $this->schema->getFieldType('supercar', 'name'));
    $this->assertFalse($this->schema->isFieldIndexed('supercar', 'name'));
    $this->assertEquals('boolean', $this->schema->getFieldType('supercar', 'carbon_fiber'));
    $this->assertTrue($this->schema->isFieldIndexed('supercar', 'carbon_fiber'));
  }
  
}