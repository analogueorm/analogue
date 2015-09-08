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

    public function testEntityRegistration()
    {
        $analogue = get_analogue();
        $this->assertFalse($analogue->isRegisteredEntity('AnalogueTest\App\Register'));
        $analogue->register('AnalogueTest\App\Register');
        $this->assertTrue($analogue->isRegisteredEntity('AnalogueTest\App\Register'));
        $this->setExpectedException('Analogue\ORM\Exceptions\MappingException');
        $analogue->register('AnalogueTest\App\Register');
        $this->setExpectedException('Analogue\ORM\Exceptions\MappingException');
        $analogue->register('AnalogueTest\App\NonExisting');
    }



}
