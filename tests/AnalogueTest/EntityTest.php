<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;

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

        $z = $mapper->whereLabel('lazycoll')->first();

        $this->assertEquals(2, $z->permissions->count());
    }

    public function testHiddenAttributes() 
    {

    }

}
