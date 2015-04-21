<?php
use App\Core\Datastore\IdentityMap;

class Food extends App\Core\Datastore\Model { }

class IdentityMapTest extends TestCase
{

    public function setUp()
    {
        $this->map = new IdentityMap();
    }

    public function testHas()
    {
        $this->assertFalse($this->map->has('food', 'chicken-bbq'));
    }

    public function testManage()
    {
        $chicken_bbq = new Food();
        $chicken_bbq->id = 'chicken-bbq';
        $chicken_bbq->name = 'Chicken Barbeque';
        $this->map->manage($chicken_bbq);
        $their_chicken_bbq = $this->map->get('food', 'chicken-bbq');
        $this->assertTrue($this->map->has('food', 'chicken-bbq'));
        $this->assertSame($chicken_bbq, $their_chicken_bbq);
    }

    public function testForget()
    {
        $belly = new Food();
        $belly->id = 190;
        $belly->name = 'Grilled Pork Belly';

        $this->assertFalse($this->map->has('food', 190));
        $this->map->manage($belly);
        $this->assertTrue($this->map->has('food', 190));
        $this->map->forget($belly);
        $this->assertFalse($this->map->has('food', 190));
    }

    public function testUpdateKey()
    {
        $sugba = new Food();
        $sugba->name = 'Sinugbang Bangus';

        $this->map->manage($sugba);
        $this->assertFalse($this->map->has('food', 5881));
        $sugba->id = 5881;
        $this->map->updateKey($sugba);
        $this->assertTrue($this->map->has('food', 5881));
    }

}