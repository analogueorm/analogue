<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityCollection;


class MapperTest extends PHPUnit_Framework_TestCase {

    public function testMapperFactory()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertInstanceOf('Analogue\ORM\System\Mapper', $mapper);
        $this->assertInstanceOf('Analogue\ORM\EntityMap', $mapper->getEntityMap());
        $this->assertInstanceOf('Analogue\ORM\System\EntityCache', $mapper->getEntityCache());
        $this->assertInstanceOf('Analogue\ORM\System\Query', $mapper->query());
        $this->assertInstanceOf('Analogue\ORM\System\Query', $mapper->getQuery());
        $this->assertInstanceOf('Analogue\ORM\System\Query', $mapper->globalQuery());
        $this->assertInstanceOf('Analogue\ORM\Entity', $mapper->newInstance());

    }

    public function testEntityNewInstanceHydration()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');
        $attributes = [
            'column1' => 1,
            'column2' => "2",
        ];
        $entity = $mapper->newInstance($attributes);
        $this->assertEquals($entity->getEntityAttributes(), $attributes);
    }

    public function testCreateOnSecondConnections()
    {
        $u = new User('gege', new Role('azdplzdaplzda'));
        $ext = new External('e1');
        $ext->user = $u;
        $eM = get_mapper($ext);
        $eM->store($ext);
        $this->assertGreaterThan(0, $ext->id);
    }

    public function testUpdateOnAdditionnalConnection()
    {
        $u = new User('polo', new Role('role'));
        $ext = new External('e1');
        $ext->user = $u;
        $eM = get_mapper($ext);
        $eM->store($ext);

        $q = $eM->whereName('e1')->first();
        
        $q->user->email = 'echanged';
        
        $eM->store($q);
        $z = $eM->find($q->id);

        $this->assertEquals('echanged', $z->user->email);
    }

    public function testCrossConnectionEagerLoadingWithBelongsTo()
    {
        $u = new User('gege44', new Role('zadaazdplzdaplzda'));
        $ext = new External('e33');
        $ext->user = $u;
        $eM = get_mapper($ext);
        $eM->store($ext);
        $q = $eM->whereName('e33')->with('user')->first();
        $this->assertEquals('gege44',$q->user->email);
    }    

    public function testCrossConnectionEagerLoadingWithHasMany()
    {
        $u = new User('gege555', new Role('zadaazdplzdaplzda'));
        $ext = new External('e555');
        $ext->user = $u;
        $eM = get_mapper($ext);
        $eM->store($ext);
        $uM = get_mapper($u);
        $q = $uM->whereEmail('gege555')->with('externals')->first();
        $this->assertEquals('e555',$q->externals[0]->name);
    }    


    public function testCrossConnectionRelationshipPivot()
    {
        $u = new User('gege666', new Role('zadaazdplzdaplzda'));
        $ext1 = new External('piv');
        $ext1->user_id = 0;
        $ext2 = new External('piv');
        $ext1->user_id = 0;
        $ext3 = new External('piv');
        $ext1->user_id = 0;
        $u->externalpivots = new EntityCollection([$ext1,$ext2,$ext3]);
        $uM = get_mapper($u);
        $uM->store($u);

    }    

    public function testCustomCommand()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $mapper->addCustomCommand('AnalogueTest\App\CustomCommand');

        $this->assertEquals(true, $mapper->hasCustomCommand('customCommand'));
        $this->assertEquals('executed' , $mapper->customCommand($mapper->newInstance()));
    }



    public function testRedirectCallsOnNewQuery()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertEquals('entities', $mapper->getTable());
    }

    public function testStoreAndHydrateAllColumnTypes()
    {
        $r = new Resource('columns');
        $string = ' test ';
        $boolean = true;
        $integer = 10;
        $date = \Carbon\Carbon::now();
        $dateTime = \Carbon\Carbon::now();
        $time = \Carbon\Carbon::now();
        $decimal = 12345.12;
        $double = 1234512.12345;
        $enum = 'a';
        $float = 0.12345;
        $json = json_encode(['a' => '1', 'b' => '2']);
        $r->foo_string = $string;
        $r->foo_boolean= $boolean;
        $r->foo_integer =$integer;
        $r->foo_date = $date;
        $r->foo_dateTime = $dateTime;
        $r->foo_time = $time;
        $r->foo_decimal = $decimal;
        $r->foo_double = $double;
        $r->foo_enum = $enum;
        $r->foo_float = $float;
        $r->foo_json = $json;
        $m=get_mapper($r);
        $m->store($r);
        $q=$m->whereName('columns')->first();
        
        $this->assertEquals($string, $q->getEntityAttribute('foo_string'));
        $this->assertEquals($boolean, $q->getEntityAttribute('foo_boolean'));
        $this->assertEquals($integer, $q->getEntityAttribute('foo_integer'));
        $this->assertEquals($date, $q->getEntityAttribute('foo_date'));
        $this->assertEquals($dateTime, $q->getEntityAttribute('foo_dateTime'));
        $this->assertEquals($time, $q->getEntityAttribute('foo_time'));
        $this->assertEquals($decimal, $q->getEntityAttribute('foo_decimal'));
        $this->assertEquals($double, $q->getEntityAttribute('foo_double'));
        $this->assertEquals($enum, $q->getEntityAttribute('foo_enum'));
        $this->assertEquals($float, $q->getEntityAttribute('foo_float'));
        $this->assertEquals($json, $q->getEntityAttribute('foo_json'));
    }

    public function testGetMapperWithInstances()
    {
        $analogue = get_analogue();

        $permissionMapper = $analogue->mapper(new Permission('PFH'), new PermissionMap);

        $this->assertInstanceOf('Analogue\ORM\System\Mapper', $permissionMapper);
    }

    public function testGetMapperWithStrings()
    {
        $analogue = get_analogue();

        $permissionMapper = $analogue->mapper('AnalogueTest\App\Permission', 'AnalogueTest\App\PermissionMap');

        $this->assertInstanceOf('Analogue\ORM\System\Mapper', $permissionMapper);
    }

    public function testLazyLoadingOnCollection()
    {
        $userMapper = get_mapper('AnalogueTest\App\User');

        $u1 = new User('michel', new Role('lr1'));
        $u2 = new User('bono', new Role('lr2'));

        $userMapper->store([$u1,$u2]);

        $id1 = $u1->id;
        $id2 = $u2->id;

        $this->assertFalse($id1 == $id2);

        $q = $userMapper->whereEmail('michel')->orWhere('email','=','bono')->orderBy('email')->get();
        $this->assertEquals(2, $q->count());
        
        $this->assertEquals('lr2', $q[0]->role->label);
        $this->assertEquals('lr1', $q[1]->role->label);

    }

    public function testStoreAndHydrateLargeSets()
    {
        $pM = get_mapper('AnalogueTest\App\Permission');
        $perms =[];
        for($x=0;$x<1000;$x++)
        {
            $perms[] = new Permission('large');
        }
        $pM->store($perms);
        $q = $pM->whereLabel('large')->get();
        $this->assertEquals(1000, $q->count());

    }
}
