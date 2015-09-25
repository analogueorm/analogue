<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityCollection;


class CascadingDeletesTest extends PHPUnit_Framework_TestCase {

    protected function setupEntities()
    {
        $user = new User('John', new Role('Housekeeper'));

        $external1 = new External('a');
        $external2 = new External('b');
        $externals = new EntityCollection([$external1, $external2]);
        $user->externals = $externals;

        $userMapper = get_mapper($user);
        $userMapper->store($user);

        return [$userMapper, $user];
    }

    public function testCascadingDeletesOnHasMany()
    {
        list($userMapper, $user) = $this->setupEntities();

        $external = $user->externals[0];
        $externalMapper = get_mapper($external);
        $this->assertEquals($externalMapper->count(), 2);

        $userMapper->delete($user);

        $this->assertEquals($externalMapper->count(), 0);
    }

    public function testCascadingDeletesOnBelongsTo()
    {
        list($userMapper, $user) = $this->setupEntities();

        $role = $user->role;
        $roleMapper = get_mapper($role);
        $this->assertEquals($roleMapper->count(), 1);

        $userMapper->delete($user);

        $this->assertEquals($roleMapper->count(), 0);
    }

}
