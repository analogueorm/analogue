<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;

class ManagerTest extends PHPUnit_Framework_TestCase {

    public function testAnalogueConnection()
    {
        $class = get_class(get_analogue());

        $this->assertEquals($class, 'Analogue\ORM\Analogue');
    }

    public function testMapperInit()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertEquals(get_class($mapper), 'Analogue\ORM\System\Mapper');
    }
}
