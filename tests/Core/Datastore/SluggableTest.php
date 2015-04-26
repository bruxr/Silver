<?php
use App\Core\Datastore\Model;
use App\Core\Datastore\Sluggable;

class SluggableTest extends TestCase {

    public function setUp()
    {
        $this->ds = $this->getMockBuilder('App\Core\Datastore\Datastore')
                   ->disableOriginalConstructor()
                   ->getMock();
        $this->ds->method('listIds')
           ->willReturn(['brux-romuar', 'unknown-guy', 1533238]);
    }

    public function testSluggable()
    {
        $p = new SluggablePerson([], $this->ds);
        $p->name = 'John Doe';
        $this->assertEquals('john-doe', $p->id);
    }

    public function testDuplicationPrevention($value='')
    {
        $p = new SluggablePerson([], $this->ds);
        $p->name = 'Unknown Guy';
        $this->assertEquals('unknown-guy2', $p->id);
    }

}

class SluggablePerson extends Model {
    use Sluggable;

    protected function setup()
    {
        $this->slugify('name');
    }
}