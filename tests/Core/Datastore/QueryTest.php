<?php

use App\Core\Datastore\Query;

class QueryTest extends TestCase
{
  
  public function setUp()
  {
    $this->q = new Query('food');
  }
  
  public function testInstantiable()
  {
    $this->assertInstanceOf('App\Core\Datastore\Query', $this->q);
  }
  
  public function testSelectAll()
  {
    $this->assertEquals('SELECT * FROM food', $this->q->getQuery());
  }
  
  public function testSelectCustom()
  {
    $this->q->select('name', 'ingredients');
    $this->assertEquals('SELECT id, name, ingredients FROM food', $this->q->getQuery());
  }
  
  public function testSimpleWhere()
  {
    $this->q->where('id', 25512);
    $this->assertEquals('SELECT * FROM food WHERE id = :id', $this->q->getQuery());
    $this->assertEquals([':id' => 25512], $this->q->getParams());
  }
  
  public function testSimpleWhereWithCustomOp()
  {
    $this->q->where('height', '>=', 6.0);
    $this->assertEquals('SELECT * FROM food WHERE height >= :height', $this->q->getQuery());
    $this->assertEquals([':height' => 6.0], $this->q->getParams());
  }
  
  public function testCompoundAndWhere()
  {
    $this->q->where('delicious', true)->andWhere('rating', '>', 3.0);
    $this->assertEquals('SELECT * FROM food WHERE delicious = :delicious AND rating > :rating', $this->q->getQuery());
    $this->assertEquals([':delicious' => true, ':rating' => 3.0], $this->q->getParams());
  }
  
  public function testCompoundOrWhere()
  {
    $this->q->where('fried', true)->orWhere('soup', true);
    $this->assertEquals('SELECT * FROM food WHERE fried = :fried OR soup = :soup', $this->q->getQuery());
    $this->assertEquals([':fried' => true, ':soup' => true], $this->q->getParams());
  }
  
  public function testCustomWhere()
  {
    $d = new DateTime();
    $params = ['slug' => 'the-post', 'created' => $d];
    $this->q->where('slug = :slug AND created > :created', $params);
    $this->assertEquals('SELECT * FROM food WHERE slug = :slug AND created > :created', $this->q->getQuery());
    $this->assertEquals($params, $this->q->getParams());
  }
  
  public function testGroupBy()
  {
    $this->q->groupBy('type');
    $this->assertEquals('SELECT * FROM food GROUP BY type', $this->q->getQuery());
  }
  
  public function testOrderBy()
  {
    $this->q->orderBy('rating', 'DESC');
    $this->assertEquals('SELECT * FROM food ORDER BY rating DESC', $this->q->getQuery());
  }
  
  public function testLimit()
  {
    $this->q->limit(50);
    $this->assertEquals('SELECT * FROM food LIMIT 50', $this->q->getQuery());
  }
  
  public function testOffset()
  {
    $this->q->offset(10);
    $this->assertEquals('SELECT * FROM food OFFSET 10', $this->q->getQuery());
  }
  
}