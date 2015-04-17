<?php

use App\Core\Datastore\Query;

class QueryTest extends TestCase
{
  
  public function setUp()
  {
    $this->ds = $this->getMockBuilder('App\Core\Datastore\DS')
                     ->disableOriginalConstructor()
                     ->getMock();
    $this->q = new Query($this->ds, 'food');
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
    $this->q->select('id', 'name', 'ingredients');
    $this->assertEquals('SELECT id, name, ingredients FROM food', $this->q->getQuery());
  }
  
  public function testSimpleWhere()
  {
    $this->q->where('id', 25512);
    $this->assertEquals('SELECT * FROM food WHERE id = ?', $this->q->getQuery());
    $this->assertEquals([25512], $this->q->getParams());
  }
  
  public function testSimpleWhereWithCustomOp()
  {
    $this->q->where('height', '>=', 6.0);
    $this->assertEquals('SELECT * FROM food WHERE height >= ?', $this->q->getQuery());
    $this->assertEquals([6.0], $this->q->getParams());
  }
  
  public function testCompoundAndWhere()
  {
    $this->q->where('delicious', true)->andWhere('rating', '>', 3.0);
    $this->assertEquals('SELECT * FROM food WHERE delicious = ? AND rating > ?', $this->q->getQuery());
    $this->assertEquals([true, 3.0], $this->q->getParams());
  }
  
  public function testCompoundOrWhere()
  {
    $this->q->where('fried', true)->orWhere('soup', true);
    $this->assertEquals('SELECT * FROM food WHERE fried = ? OR soup = ?', $this->q->getQuery());
    $this->assertEquals([true, true], $this->q->getParams());
  }
  
  public function testCustomWhere()
  {
    $d = new DateTime();
    $this->q->where('slug = ? AND created > ?', ['the-post', $d]);
    $this->assertEquals('SELECT * FROM food WHERE slug = ? AND created > ?', $this->q->getQuery());
    $this->assertEquals(['the-post', $d], $this->q->getParams());
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