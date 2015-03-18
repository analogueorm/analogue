<?php namespace AnalogueTest\App;

use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Analogue\ORM\EntityCollection;

class FullDomainTest extends PHPUnit_Framework_TestCase {

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
           
    }

    public function testEntityLazyCollection()
    {

    }


}
