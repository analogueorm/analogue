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
        $pm = get_mapper('AnalogueTest\App\Permission');
        $cc = $pm->whereLabel('p33')->first();
        $this->assertEquals('p33', $cc->label);
        $z = $mapper->whereLabel('lazycoll')->first();

        $this->assertEquals(2, $z->permissions->count());
    }

    public function testFlattenValueObject()
    {
        $res = new Resource('name');
        
        $array = $res->toArray();
        $this->assertTrue(array_key_exists('v_field_2', $array));
        $this->assertFalse(array_key_exists('v', $array));
    }

    public function testHiddenAttributes() 
    {
        $res = new Resource('name');
        $array = $res->toArray();
        
        $this->assertFalse(array_key_exists('name', $array));
        $this->assertFalse(array_key_exists('v_field_1', $array));
        $this->assertTrue(array_key_exists('v_field_2', $array));
    }

    public function testMutators()
    {
        $res = new Resource('name');
        $res->string = 'momo';
        $this->assertEquals('mutated_momo_mutated', $res->string);
    }

    public function testSetMutators()
    {
        
    }

    public function testParsingLazyLoadedCollections()
    {

    }
}
