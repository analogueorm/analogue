<?php

namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;

class PlainObjectTest extends PHPUnit_Framework_TestCase
{
    public function testPopoStore()
    {
        $popo = new Popo('popo1');

        $mapper = get_mapper($popo);

        $popoMap = $mapper->getEntityMap();

        $this->assertEquals('AnalogueTest\App\PopoMap', get_class($popoMap));
        $this->assertEquals(['id','user','name'], $popoMap->getCompiledAttributes() );

        $mapper->store($popo);

        $this->assertGreaterThan(0, $popo->getId());
    }

    public function testPopoSingleRelationship()
    {
        $popo = new Popo('popo2');

        $mapper = get_mapper($popo);

        $user = new User('popo@popo.com', new Role('popouser'));

        $popo->setUser($user);

        $mapper->store($popo);
    }
}
