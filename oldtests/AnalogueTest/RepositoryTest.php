<?php

namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Repository;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityCollection;
use Illuminate\Support\Collection;
use AnalogueTest\App\Permission;
use AnalogueTest\App\Role;

class RepositoryTest extends PHPUnit_Framework_TestCase
{
    protected function getRepository()
    {
        $analogue = get_analogue();
        return $analogue->repository('AnalogueTest\App\Permission');
    }

    public function testRepositoryInstantiation()
    {
        $analogue = get_analogue();
        $entity = new Entity;
        $repo = new Repository($analogue->mapper($entity));
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
        $repo = new Repository($entity);
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
        $repo = new Repository(get_class($entity));
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
    }

    public function testStoreThenFind()
    {
        $repo = $this->getRepository();
        $p = new Permission('Puzzle');
        $repo->store($p);
        $id=$p->id;
        $this->assertGreaterThan(0, $id);
        $q = $repo->find($id);
        $this->assertEquals('Puzzle', $q->label);
    }

    public function testFirstMatch()
    {
        $repo = $this->getRepository();
        $p = new Permission('First');
        $q = new Permission('Second');
        $repo->store([$p, $q]);
        $id = $q->id;
        $r = $repo->firstMatching(['label' => 'Second']);
        $this->assertEquals('Second', $r->label);
        $this->assertEquals($id, $r->id);
    }


    public function testAllMatch()
    {
        $repo = $this->getRepository();
        $p = new Permission('Third');
        $q = new Permission('Third');
        $repo->store([$p, $q]);
        $r = $repo->allMatching(['label' => 'Third']);

        $this->assertInstanceOf('Analogue\ORM\EntityCollection', $r);
        $this->assertEquals(2, $r->count());
    }

    public function testPaginate()
    {
        $repo = $this->getRepository();
        $p = new Permission('First');
        $q = new Permission('Second');
        $repo->store([$p, $q]);

        $s = $repo->paginate(1);
        $this->assertEquals(1, count($s));
    }

    public function testStoreThenDelete()
    {
        $repo = $this->getRepository();
        $p = new Permission('Trash');
        $repo->store($p);
        $id=$p->id;
        $this->assertGreaterThan(0, $id);
        $repo->delete($p);
        $q = $repo->find($id);
        $this->assertNull($q);
    }

    public function testStoreEntityWithChangedRelation()
    {
        $analogue = get_analogue();
        $userRepo = $analogue->repository('AnalogueTest\App\User');

        // User has role id of 1
        $user = $userRepo->find(1);

        $roleRepo = $analogue->repository('AnalogueTest\App\Role');
        
        // Set user role to be role with id 2
        $user->role = $roleRepo->find(2);

        $userRepo->store($user);
 
        // Refetch $user from repo
        $user = $userRepo->find(1);
        
        // User's role_id should now be 2
        $this->assertEquals("2", $user->role_id);
    }

    public function testStoreEntityWithChangedMultiRelation()
    {
        $analogue = get_analogue();
        $roleRepo = $analogue->repository('AnalogueTest\App\Role');
        $permissionRepo = $this->getRepository();

        $mapper = get_mapper(Permission::class);
        
        
        // Add two roles to permission
        $permission = $permissionRepo->find(14); 
        

        $permission->roles = new EntityCollection([
            $roleRepo->find(1),
            $roleRepo->find(2),
        ]);

        $permissionRepo->store($permission);

        $permission = $permissionRepo->find(14); 
        $this->assertEquals(2, count($permission->roles));

        $role = new Role('A new one that did not exists');
        $role2 = new Role('Another Role that did not exists');
        $roleRepo->store([$role, $role2]);
        $permission->roles = new Collection([
            $roleRepo->find($role->id),
            $roleRepo->find($role2->id),                       
        ]);
        $permissionRepo->store($permission);
        // Reload from repo
        $permission = $permissionRepo->find(14); 
        $this->assertEquals(2, $permission->roles->count());


        // Change permission to only have a single role
        $permission->roles = new Collection([
            $roleRepo->find(1),
        ]);
        $permissionRepo->store($permission);
        // Reload from repo
        $permission = $permissionRepo->find(14); 
        //$permission->roles->count();
        // Permission should only have one role
        $this->assertEquals(1, count($permission->roles));
    }
}
