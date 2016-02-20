<?php

namespace AnalogueTest;

use PHPUnit_Framework_TestCase;

class ManagerTest extends PHPUnit_Framework_TestCase
{
    public function testAnalogueConnection()
    {
        $class = get_class(get_analogue());

        $this->assertEquals('Analogue\ORM\Analogue', $class);

        // Test Helper Function
        $class = get_class(analogue());

        $this->assertEquals('Analogue\ORM\System\Manager', $class);
    }

    public function testMapperInit()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertEquals(get_class($mapper), 'Analogue\ORM\System\Mapper');
    }

    public function testEntityRegistration()
    {
        $analogue = get_analogue();
        $this->assertFalse($analogue->isRegisteredEntity('AnalogueTest\App\NoMap'));
        $analogue->setStrictMode(false);
        $analogue->register('AnalogueTest\App\NoMap');
        $this->assertTrue($analogue->isRegisteredEntity('AnalogueTest\App\Avatar'));
        $this->setExpectedException('Analogue\ORM\Exceptions\MappingException');
        $analogue->register('AnalogueTest\App\Register');
        $this->setExpectedException('Analogue\ORM\Exceptions\MappingException');
        $analogue->register('AnalogueTest\App\NonExisting');
        
        $this->setExpectedException('Analogue\ORM\Exceptions\EntityMapNotFoundException');
        $analogue->setStrictMode(true);
        $analogue->register('AnalogueTest\App\NoMap');

    }

    public function testAutoDetectEntityMap()
    {
        $mapper = get_mapper('AnalogueTest\App\User');
        $this->assertEquals('AnalogueTest\App\UserMap', get_class($mapper->getEntityMap()));
    }
}
