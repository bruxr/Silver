<?php

class GCDTest extends TestCase
{
  
  public function setUp()
  {
    $field_types = [
      ['food', 'name', 'string'],
      ['food', 'ingredients', 'list'],
      ['food', 'edible', 'boolean'],
      ['food', 'created', 'datetime']
    ];
    $field_indices = [
      ['food', 'name', false],
      ['food', 'ingredients', false]
    ];
    $schema = $this->getMockBuilder('App\Core\Datastore\Schema')
                   ->disableOriginalConstructor()
                   ->getMock();
    $schema->method('getFieldType')
           ->will($this->returnValueMap($field_types));
    $schema->method('isFieldIndexed')
           ->will($this->returnValueMap($field_indices));
    
    $this->gcd = new App\Core\Datastore\Drivers\GCD(getenv('APP_ID'), $this->app->container['google_client'], $schema);
  }


  public function testCreate()
  {
    $item = $this->gcd->create('food', [
      'name' => 'Kare-kare',
      'ingredients' => ['Peanut Butter', 'Vegetables']
    ]);
    $this->assertArrayHasKey('id', $item);
  }
  
  public function testCreateWithName()
  {
    $this->gcd->create('food', [
      'id' => 'hamburger',
      'name' => 'Hamburger',
      'ingredients' => ['Bun', 'Patty']
    ]);
  }
  
  public function testCreateWithAncestors()
  {
    $this->gcd->create('food', [
      'name' => 'Spagheeto',
      'ingredients' => ['Pasta', 'Sauce', 'Cheese'],
      '_ancestors' => [['owner', 'brux']]
    ]);
  }
  
  public function testCreateWithNameAndAncestors()
  {
    $this->gcd->create('food', [
      'id' => '4-seasons',
      'name' => '4 Seasons',
      'ingredients' => ['Pineapple', 'Watermelon', 'Orange', 'Apple'],
      '_ancestors' => [['owner', 'brux']]
    ]);
  }
  
  public function testFindWithId()
  {
    sleep(30); // wait for the data to be available
    $item = $this->gcd->find('SELECT * FROM food WHERE id = :id', [':id' => 'hamburger'])[0];
    $this->assertEquals('hamburger', $item['id']);
    $this->assertEquals('Hamburger', $item['name']);
    $this->assertContains('Bun', $item['ingredients']);
    $this->assertContains('Patty', $item['ingredients']);
  }
  
  public function testFindMany()
  {
    $items = $this->gcd->find('SELECT * FROM food');
  }

  public function testListIds()
  {
    $ids = $this->gcd->listIds('food');
    $this->assertNotEmpty($ids);
  }
  
  public function testUpdate()
  {
    $this->assertTrue($this->gcd->update('food', [
      'id' => 'unknown-food',
      'name' => 'Unknown Food',
      'ingredients' => ['Unknown 1', 'Unknown 2'],
      'edible' => false
    ]));
  }
  
  public function testDelete()
  {
    $this->assertTrue($this->gcd->delete('food', 'unknown-food'));
  }
  
}