<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use AnalogueTest\App\Meta;
use AnalogueTest\App\User;
use AnalogueTest\App\Role;

class ValueObjectTest extends PHPUnit_Framework_TestCase {

    public function testValueObjectStore()
    {
        $user = new User('boris', new Role('meta'));
        $uMapper = get_mapper($user);
        $user->metas->set('key', 'value');
        $uMapper->store($user);
        $q = $uMapper->whereEmail('boris')->first();
        //tdd($q);
        $this->assertEquals('value', $q->metas->get('key'));
    }

}
