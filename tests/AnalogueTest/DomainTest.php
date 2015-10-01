<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Analogue\ORM\EntityCollection;

class DomainTest extends PHPUnit_Framework_TestCase {

    public function testStoreRoleUser()
    {
        $analogue = get_analogue();

        $role = new Role('admin');

        $analogue->mapper($role)->store($role);
        $this->assertGreaterThan(0, $role->id);
   
        $user = new User('alice@example.com', $role);
        $analogue->mapper($user)->store($user);
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
        $this->assertInstanceOf('Analogue\ORM\System\Proxies\EntityProxy', $rawAttributes['role']);
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
        
        $this->assertInstanceOf('Analogue\ORM\System\Proxies\CollectionProxy', $rawAttributes['permissions']);
        $this->assertInstanceOf('Analogue\ORM\EntityCollection', $rawAttributes['permissions']->load());
        $this->assertEquals($perms->lists('label'), $ur->permissions->lists('label')); 
    }

    public function testEagerLoading()
    {
        $analogue = get_analogue();

        $ur=$analogue->query('AnalogueTest\App\Role')->with(['users','permissions'])->whereLabel('user')->first();
        //tdd($ur);
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

        //setDebugOn();

        $analogue->mapper($resource)->store($resource);

        //setDebugOff();

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

    public function testDetachMissingRelationships()
    {
        
        $user = new User('testmissing', new Role('missingRole'));
        $userMapper = get_mapper($user);
        $avatar1 = new Avatar('avatar1');
        $avatar2 = new Avatar('avatar2');
        $user->avatars = new EntityCollection([$avatar1, $avatar2]);
        $userMapper->store($user);

        $userId = $user->id;
        $avatar1id = $avatar1->id;
        $avatar2id = $avatar2->id;
        $this->assertGreaterThan(0, $avatar1id);
        $this->assertGreaterThan(0, $avatar2id);

        $user->avatars = null;
        $userMapper->store($user);

        $user = $userMapper->with('avatars')->whereId($user->id)->first();

        $this->assertEquals(0, $user->avatars->count());
    }

    public function testRelationResetOnHasMany()
    {
        
        $user = new User('relationsync', new Role('relationsSync'));
        $userMapper = get_mapper($user);
        $avatar1 = new Avatar('before-avatar-1');
        $avatar2 = new Avatar('before-avatar-2');
        $user->avatars =new EntityCollection([$avatar1, $avatar2]);
        $userMapper->store($user);

        // Make a find() operation on user, which will reset the cache
        $q = $userMapper->find($user->id);
        $this->assertInstanceOf('Analogue\ORM\System\Proxies\ProxyInterface', $q->avatars);
        $avatar3 = new Avatar('after-avatar-1');
        $q->avatars = new Collection([$avatar3]);
        $userMapper->store($q);

        // Make a find() operation on user, which will reset the cache
        $q = $userMapper->with('avatars')->find($user->id);
        $this->assertEquals(1, $q->avatars->count());
    }

    public function testStoringWithInverseRelationship()
    {
        $user = new User('inverserelation', new Role('inverserole'));
        $userMapper = get_mapper($user);
        $avatar = new Avatar('avatar-xyz', $user);
        $user->avatars = [$avatar];
        $userMapper->store($user);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testUpdatingDirtyRelationships()
    {
        $user = new User('dirtyrelated', new Role('dirtyrole'));
        $userMapper = get_mapper($user);
        $avatar = new Avatar('avatar-initial', $user);
        $user->avatars = new Collection([$avatar]);
        $userMapper->store($user);
        $this->assertGreaterThan(0, $user->id);
        $id = $user->id;
        $user->avatars->first()->name = 'avatar-modified';
        $userMapper->store($user);
        $q = $userMapper->find($id);
        $this->assertEquals('avatar-modified', $user->avatars->first()->name);
    }

    public function testLazyLoadingOnHasMany()
    {
        $user = new User('test-lazy-has-many', new Role('lazy-has-many'));
        $userMapper = get_mapper($user);
        $avatar1 = new Avatar('avat1');
        $avatar2 = new Avatar('avat2');
        $user->avatars =new EntityCollection([$avatar1, $avatar2]);
        $userMapper->store($user);
        $userId = $user->id;
        $avatarId1 = $avatar1->id;
        $avatarId2 = $avatar2->id;
        $this->assertGreaterThan(0, $userId);
        $this->assertGreaterThan(0, $avatarId1);
        $this->assertGreaterThan(0, $avatarId2);
        $q = $userMapper->find($userId);
        $this->assertEquals(2, $q->avatars->count());
    }

    public function testLazyLoadingOnMorphMany()
    {
        $resource = new Resource('lazy-morph-many');
        $image1 = new Image('Image1');
        $image2 = new Image('Image2');
        $resource->images = new Collection([$image1,$image2]);
        $resourceMapper = get_mapper($resource);
        $resourceMapper->store($resource);
        $this->assertGreaterThan(0, $resource->custom_id);
        $this->assertGreaterThan(0, $image1->id);
        $q = $resourceMapper->find($resource->custom_id);
        $this->assertEquals(2, $q->images->count());
    }

    public function testLazyLoadingOnMorphOne()
    {
        $avatar = new Avatar('lazyloadingavatar');
        $avatar->image = new Image('avatar-image-lazy');
        $mapper = get_mapper($avatar);
        $mapper->store($avatar);
        $id = $avatar->id;
        $imageId = $avatar->image->id;
        $this->assertGreaterThan(0, $id);
        $this->assertGreaterThan(0, $imageId);
        $avatar = $mapper->find($id);
        $image = $avatar->image;
        $this->assertEquals($imageId, $image->id);
    }


    public function testRecursiveRelationships()
    {
        $user = new User('recursions', new Role('recursion'));
        $userMapper = get_mapper($user);
        $avatar = new Avatar('avatar-with-image', $user);
        $avatar->image = new Image('avatar-image');
        $user->avatars = new Collection([$avatar]);
        $userMapper->store($user);
        $id = $user->id;
        $this->assertGreaterThan(0, $user->id);
        $this->assertGreaterThan(0, $user->avatars->first()->id);
        $this->assertGreaterThan(0, $user->avatars->first()->image->id);

        $imageId = $user->avatars->first()->image->id;
        $imageMapper=get_mapper(new Image('ezfzefj'));
        $imageObject = $imageMapper->find($imageId);
       
        // Update image path (2 level deep relationship)z
        $q = $userMapper->find($id);
        $avatar = $q->avatars->first();
        $avatarId = $avatar->id;

        $avatarMapper = get_mapper($avatar);

        $a = $avatarMapper->with('image')->find($avatarId);
        $this->assertInstanceOf('AnalogueTest\App\Image', $a->image);
               

        $q->avatars->first()->image->setPath("new-path");
        $image = $q->avatars->first()->image;

        $image->setPath("new-path");
        $userMapper->store($q);

        $q = $userMapper->find($id);

        $this->assertEquals('new-path', $q->avatars->first()->image->path);
    }

}
