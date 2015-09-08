<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Analogue\ORM\EntityCollection;

class DomainTest extends PHPUnit_Framework_TestCase {

    public function testStoreRoleUser()
    {
        $analogue = get_analogue();

        $role = new Role('admin');

        $user = new User('alice@example.com', $role);

        $analogue->mapper($user)->store($user);

        $this->assertGreaterThan(0, $role->id);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testSetCustomPrimaryKey()
    {
        $analogue = get_analogue();

        $resource = new Resource('mars');

        $analogue->mapper($resource)->store($resource);

        $this->assertGreaterThan(0, $resource->custom_id);
    }

    public function testStoreMultipleRecordsAsArray()
    {
        $analogue = get_analogue();

        $a = new Permission('P1');
        $b = new Permission('P2');
        $c = new Permission('P3');

        $analogue->mapper($a)->store([$a,$b,$c]);

        $this->assertGreaterThan(0, $a->id);
        $this->assertGreaterThan(0, $b->id);
        $this->assertGreaterThan(0, $c->id);
    }

    public function testStoreMultipleRecordsAsCollection()
    {
        $analogue = get_analogue();

        $a = new Permission('P1');
        $b = new Permission('P2');
        $c = new Permission('P3');

        $analogue->mapper($a)->store(new Collection([$a,$b,$c]));

        $this->assertGreaterThan(0, $a->id);
        $this->assertGreaterThan(0, $b->id);
        $this->assertGreaterThan(0, $c->id);
    }

    public function testStoreMultipleRecordsAsEntityCollection()
    {
        $analogue = get_analogue();

        $a = new Permission('P1');
        $b = new Permission('P2');
        $c = new Permission('P3');

        $analogue->mapper($a)->store(new EntityCollection([$a,$b,$c]));

        $this->assertGreaterThan(0, $a->id);
        $this->assertGreaterThan(0, $b->id);
        $this->assertGreaterThan(0, $c->id);
    }

    public function testStoreThenDeleteThenRestore()
    {
        $analogue = get_analogue();

        $a = new Permission('P1');
        
        $analogue->mapper($a)->store($a);
        $this->assertGreaterThan(0, $a->id);
        $analogue->mapper($a)->delete($a);
        $this->assertNull($a->id);
        $this->setExpectedException('Analogue\ORM\Exceptions\MappingException');
        $analogue->mapper($a)->delete($a);
    }


    public function testLazyLoadingEntity()
    {
        $analogue = get_analogue();

        $role = new Role('guest');

        $user = new User('bob@example.com', $role);

        $analogue->mapper($user)->store($user);

        $user = null;
        $role = null;

        $bob = $analogue->query('AnalogueTest\App\User')->whereEmail('bob@example.com')->first();
        
        $rawAttributes = $bob->getEntityAttributes();
        $this->assertInstanceOf('Analogue\ORM\System\EntityProxy', $rawAttributes['role']);
        $this->assertInstanceOf('AnalogueTest\App\Role', $bob->role);
        $this->assertEquals('guest', $bob->role->label);        
    }

    public function testEntityLazyCollection()
    {
        $analogue = get_analogue();

        $role = new Role('user');

        $a = new Permission('P1');
        $b = new Permission('P2');
        $c = new Permission('P3');

        $perms = new EntityCollection([$a,$b,$c]);

        $role->permissions = $perms;
        $analogue->mapper($role)->store($role);
        
        $ur=$analogue->query('AnalogueTest\App\Role')->whereLabel('user')->first();
        
        $rawAttributes = $ur->getEntityAttributes();
        
        $this->assertInstanceOf('Analogue\ORM\System\CollectionProxy', $rawAttributes['permissions']);
        $this->assertInstanceOf('Analogue\ORM\EntityCollection', $rawAttributes['permissions']->load());
        $this->assertEquals($perms->lists('label'), $ur->permissions->lists('label')); 
    }

    public function testEagerLoading()
    {
        $analogue = get_analogue();

        $ur=$analogue->query('AnalogueTest\App\Role')->with(['users','permissions'])->whereLabel('user')->first();
        $rawAttributes = $ur->getEntityAttributes();
        $this->assertInstanceOf('Analogue\ORM\EntityCollection', $rawAttributes['permissions']);
        $this->assertInstanceOf('Analogue\ORM\EntityCollection', $rawAttributes['users']);
    }

    public function testStoreAndLoadPolymorphicManyRelations()
    {
        $analogue = get_analogue();

        $resource = new Resource('Poly');

        $u1 = new User('u1', new Role('r1'));
        $u2 = new User('u2', new Role('r2'));
        $resource->users = new EntityCollection([$u1, $u2]);

        $image1 = new Image('i1');
        $image2 = new Image('i2');
        $resource->images = new EntityCollection([$image1,$image2]);

        $analogue->mapper($resource)->store($resource);

        $this->assertGreaterThan(0, $resource->custom_id);
        
        $resourceMapper = get_mapper($resource);
        $id = $resource->custom_id;
        $res = $resourceMapper->query()->with(['users','images'])->find($id);
    }

    public function testMissingPermissions()
    {
        $mapper = get_mapper('AnalogueTest\App\Permission');

        $permissions = $mapper->query()->get();

        $mapper->delete($permissions);

        $roleMapper = get_mapper('AnalogueTest\App\Role');

        $roles = $roleMapper->query()->with('permissions')->get();

        foreach($roles as $role)
        {
            $this->assertEquals(0, $role->permissions->count());
        }
        
    }

    public function testSoftDeleteAndRestore()
    {
        $resource = new Resource('softdelete');
        $rMap = get_mapper($resource);
        $rMap->store($resource);
        //tdd($rMap);
        $id = $resource->custom_id;
        $rMap->delete($resource);
        $q = $rMap->find($id);
        $this->assertNull($q);
        $q= $rMap->withTrashed()->whereName('softdelete')->first();
        $this->assertEquals($id, $q->custom_id);
        $q= $rMap->onlyTrashed()->whereName('softdelete')->first();
        $this->assertEquals($id, $q->custom_id);
        $q= $rMap->globalQuery()->find($id);
        $this->assertEquals($id, $q->custom_id);
        $rMap->restore($q);
        $q = $rMap->find($id);
        $this->assertEquals($id, $q->custom_id);
    }

    public function testStoreManyToManyTwice()
    {
        $role = new Role('twice');
        $perm = new Permission('one_perm');
        $role->permissions->add($perm);
        $rm = get_mapper($role);
        $rm->store($role);
        $role->permissions->add(new Permission('two_perm'));
        $rm->store($role);
        $id = $role->id;
        $q = $rm->find($id);
        $this->assertEquals(2,$role->permissions->count());
        // Replace
        $q->permissions = new EntityCollection([new Permission('three_perm')]);
        $rm->store($q);
        $z = $rm->find($id);
        $this->assertEquals(1,$z->permissions->count());
    }

    public function testPivotAttributes()
    {
        $role = new Role('pivot_role');
        $perm = new Permission('pivot_perm');
        $role->permissions->add($perm);
        $rm = get_mapper($role);
        $rm->store($role);
        $id = $role->id;
        $role = $rm->with('permissions')->find($id);
        $this->assertEquals('pivot_role', $role->label);
        $this->assertEquals(1, $role->permissions->count());
        $this->assertEquals('pivot_perm', $role->permissions->first()->label);
        $role->permissions->first()->pivot->active = true;
        $attributes = $role->permissions->first()->getEntityAttributes();
        $rm->store($role);
        $roleReload = $rm->find($id);
        $this->assertEquals(true, $roleReload->permissions->first()->pivot->active);
    }

    public function testSelfGeneratedPrimaryKey()
    {
        $uuid = new Uuid('test', 'testlabel');
        $um = get_mapper($uuid);
        $um->store($uuid);
        $this->assertEquals('test', $uuid->uuid);
        $uuid = $um->where('uuid', '=', 'test')->first();
        $this->assertEquals('test', $uuid->uuid);
        $this->assertEquals('testlabel', $uuid->label);

    }
}
