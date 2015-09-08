<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use AnalogueTest\App\Meta;
use AnalogueTest\App\User;
use AnalogueTest\App\Role;
use AnalogueTest\App\Resource;
use AnalogueTest\App\V;

class ValueObjectTest extends PHPUnit_Framework_TestCase {

    public function testValueObjectStoreAndRead()
    {
        $user = new User('boris', new Role('meta'));
        $uMapper = get_mapper($user);
        $user->metas->set('key', 'value');
        $uMapper->store($user);
        $q = $uMapper->whereEmail('boris')->first();
        //tdd($q);
        $this->assertEquals('value', $q->metas->get('key'));
        $this->assertEquals(1, count($q->metas->all()));
    }

    public function testValueObjectWithMultipleFields()
    {
        $resource = new Resource('res');
        $rMapper = get_mapper($resource);
        $v= new V('v1','v2');
        $resource->value = $v;
        $rMapper->store($resource);
        $id = $resource->custom_id;
        $q = $rMapper->whereName('res')->first();
        $this->assertEquals('v1', $q->value->field_1);
        $this->assertEquals('v2', $q->value->field_2);
        // Add test for updating
        $z = $rMapper->find($id);
        $rMapper->store($z);
    }

}
