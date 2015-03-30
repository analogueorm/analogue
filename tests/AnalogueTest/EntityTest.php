<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Analogue\ORM\EntityCollection;

class EntityTest extends PHPUnit_Framework_TestCase {

    public function testAddToLazyLoadedCollection()
    {
        $mapper = get_mapper('AnalogueTest\App\Role');
        $r = new Role('lazycoll');
        $r->permissions->add(new Permission('p1'));
        $mapper->store($r);

        $q = $mapper->whereLabel('lazycoll')->first();
        $this->assertInstanceOf('Analogue\ORM\System\CollectionProxy', $q->getEntityAttribute('permissions'));
        $q->permissions->add(new Permission('p33'));
        $mapper->store($q);
        $pm = get_mapper('AnalogueTest\App\Permission');
        $cc = $pm->whereLabel('p33')->first();
        $this->assertEquals('p33', $cc->label);
        $z = $mapper->whereLabel('lazycoll')->first();

        $this->assertEquals(2, $z->permissions->count());
    }

    public function testHiddenAttributes() 
    {
        $res = new Resource('name');
        $array = $res->toArray();
        $this->assertFalse(array_key_exists('name', $array));
    }

    public function testMutators()
    {
        $res = new Resource('name');
        $res->string = 'momo';
        $this->assertEquals('mutated_momo_mutated', $res->string);
    }

    public function testMutatorsToArray()
    {
        $res = new Resource('name');
        $res->string = 'momo';
        
        $array = $res->toArray();

        $this->assertEquals('mutated_momo_mutated', $array['string']);
    }

    public function testParsingLazyLoadedCollections()
    {
        $role = new Role('lazyparser');
        $p1 = new Permission('l1');
        $p2 = new Permission('l1');
        // Doesn't work. Should consider using array as allowed value ????
        //$role->permissions = [$p1, $p2];    
        // Doesn't work. We definitely should consider support them !!
        //$perms = new Collection([$p1,$p2]);
        $perms = new EntityCollection([$p1,$p2]);
        $role->permissions = $perms;
        $roleMapper = get_mapper($role);
        $roleMapper->store($role);
        $r=$roleMapper->whereLabel('lazyparser')->first();
        foreach($r->permissions as $perm)
        {
             $this->assertEquals('l1',$perm->label);
        }
       
    }

    public function testSimpleEntityToArray()
    {
        // Before Store
        $role = new Role('toArray');
        $p1 = new Permission('l1');
        $p2 = new Permission('l1');
        $role->permissions->add($p1);
        $role->permissions->add($p2);
        $role->toArray();
        $rm = get_mapper($role);
        $rm->store($role);
        $role->toArray();
    }
}
