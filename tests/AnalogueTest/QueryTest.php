<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\EntityCollection;
use AnalogueTest\App\Permission;

class QueryTest extends PHPUnit_Framework_TestCase {

  public function testPaginationWithDefaultValue()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $c = new EntityCollection;
        $y=0;
        for($x=0;$x<30;$x++)
        {
            $c->add(new Permission("P$x"));
        }
        $mapper->store($c);
        $paginator = $mapper->query()->paginate();
        $this->assertEquals(15, count($paginator));
    }

    public function testPaginationWithCustomValue()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $c = new EntityCollection;
        $y=0;
        for($x=0;$x<30;$x++)
        {
            $c->add(new Permission("P$x"));
        }
        $mapper->store($c);
        $paginator = $mapper->query()->paginate(5);
        $this->assertEquals(5, count($paginator));
    }

    public function testSimplePaginate()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $c = new EntityCollection;
        $y=0;
        for($x=0;$x<30;$x++)
        {
            $c->add(new Permission("P$x"));
        }
        $mapper->store($c);
        $paginator = $mapper->query()->simplePaginate(5);
        $this->assertEquals(5, count($paginator));
    }
}
