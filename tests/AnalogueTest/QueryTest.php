<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\EntityCollection;
use AnalogueTest\App\Permission;
use AnalogueTest\App\Avatar;
use AnalogueTest\App\Role;

class QueryTest extends PHPUnit_Framework_TestCase {

    public function testGet()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p = new Permission('p1');
        $mapper->store($p);
        $this->assertInstanceOf('Analogue\ORM\EntityCollection',$mapper->query()->get() );
    }

    public function testGetSingleColumn()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p = new Permission('p1');
        $mapper->store($p);
        $q = $mapper->query()->get(['label']);
        $this->assertInstanceOf('Analogue\ORM\EntityCollection', $q );
    }

    public function testGetTable()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $table = $mapper->query()->getTable();
        $this->assertEquals('permissions', $table);

        $mapper = get_mapper('AnalogueTest\App\Avatar');
        $table = $mapper->query()->getTable();
        $this->assertEquals('custom_avatar', $table);
    }

    public function testFind()
    {   
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p = new Permission('p1');
        $mapper->store($p);
        $id = $p->id;
        $this->assertGreaterThan(0, $id);
        $a = $mapper->query()->find($id);
        $this->assertEquals('p1', $a->label);
    }

    public function testFindNonExisting()
    {   
        $mapper = get_mapper('AnalogueTest\App\Avatar');
        $a = $mapper->query()->find(1000000);
        $this->assertNull($a);
    }


    public function testFindMany()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('P1');
        $p2 = new Permission('P2');
        $c = new EntityCollection([$p1,$p2]);
        $mapper->store($c);
        $id1 = $p1->id;
        $id2 = $p2->id;
        $q = $mapper->query()->findMany([$id1,$id2]);
        $this->assertInstanceOf('Analogue\ORM\EntityCollection',$q);
        $this->assertEquals(2, $q->count());
    }

    public function testFindOrFail()
    {
        $mapper = get_mapper('AnalogueTest\App\Avatar');
        $this->setExpectedException('Analogue\ORM\Exceptions\EntityNotFoundException');
        $a = $mapper->query()->findOrFail(1000000);
        
    }

    public function testFirst()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('P1');
        $p2 = new Permission('P2');
        $c = new EntityCollection([$p1,$p2]);
        $mapper->store($c);

        $q = $mapper->query()->whereLabel('P2')->first();

        $this->assertInstanceOf('AnalogueTest\App\Permission',$q);
    }

    public function testFirstOrFail()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $this->setExpectedException('Analogue\ORM\Exceptions\EntityNotFoundException');
        $q = $mapper->query()->whereLabel('zeuefhiuhiuhzd')->firstOrFail();
    }

    public function testPluck()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('P1');
        $mapper->store($p1);
        $id = $p1->id;
        $q = $mapper->query()->whereId($id)->pluck('label');
        $this->assertEquals('P1', $q);
    }

    public function testChunk()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('chunk');
        $p2 = new Permission('chunk');
        $c = new EntityCollection([$p1,$p2]);
        $mapper->store($c);

        $mapper->query()->whereLabel('chunk')->chunk(1, function ($result) {
            $this->assertEquals(1, count($result));
        });
    }

    public function testLists()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('chunk');
        $p2 = new Permission('norris');
        $c = new EntityCollection([$p1,$p2]);
        $mapper->store($c);

        $l = $mapper->query()->lists('label');

        $this->assertTrue(is_array($l));
    }

    public function testOrWhere()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('michael');
        $p2 = new Permission('jackson');
        $c = new EntityCollection([$p1,$p2]);
        $mapper->store($c);

        $r = $mapper->query()->whereLabel('michael')->orWhere('label', '=', 'jackson')->get();

        $this->assertEquals(2, $r->count());
    }

    public function testHas()
    {
        $mapper = get_mapper('AnalogueTest\App\Role');

        $r = new Role('producer');
        $mapper->store($r);
        $r->permissions->add(new Permission('produce'));
        $mapper->store($r);
        $q = $mapper->query()->has('permissions')->get();
        $this->assertGreaterThan(0, $q->count());

    }

    public function testWhereHas()
    {
        $mapper = get_mapper('AnalogueTest\App\Role');

        $r = new Role('banana');
        $mapper->store($r);
        $r->permissions->add(new Permission('apple'));
        $mapper->store($r);
        $role = $mapper->query()->whereHas('permissions', function($q)
        {
            $q->whereLabel('apple');
        })->first();
        $this->assertEquals('banana', $role->label);
    }

    public function testOrHas()
    {
        $mapper = get_mapper('AnalogueTest\App\Role');
        $r = new Role('orange');
        $mapper->store($r);
        $r->permissions->add(new Permission('apple'));
        $mapper->store($r);
        $s = new Role('mozart');
        $mapper->store($s);
        $roles = $mapper->query()->whereLabel('mozart')->orHas('permissions')->get();
        $this->assertGreaterThan(1, $roles->count() );
    }

    public function testOrWhereHas()
    {
        $mapper = get_mapper('AnalogueTest\App\Role');
        $r = new Role('blue');
        $mapper->store($r);
        $r->permissions->add(new Permission('momo'));
        $mapper->store($r);
        $s = new Role('green');
        $mapper->store($s);
        $roles = $mapper->query()->whereLabel('green')->orWhereHas('permissions', function($q) {
            $q->whereLabel('momo');
        })->lists('label');
        
        $this->assertEquals(['blue','green'], $roles );
    }

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

    public function testWhereBlock()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');
        $p1 = new Permission('ozzy');
        $p2 = new Permission('osbourne');
        $c = new EntityCollection([$p1,$p2]);
        $mapper->store($c);

        $r = $mapper->query()->where(function ($query) {
            $query->where('label', 'ozzy')
                  ->orWhere('label', 'osbourne');
        })->get();

        $this->assertEquals(2, $r->count());
    }
}
