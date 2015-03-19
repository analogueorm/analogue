<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Entity;
use AnalogueTest\App\CustomCommand;

class MapperTest extends PHPUnit_Framework_TestCase {

    public function testMapperFactory()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertInstanceOf('Analogue\ORM\System\Mapper', $mapper);
        $this->assertInstanceOf('Analogue\ORM\EntityMap', $mapper->getEntityMap());
        $this->assertInstanceOf('Analogue\ORM\System\EntityCache', $mapper->getEntityCache());
        $this->assertInstanceOf('Illuminate\Database\Connection', $mapper->getConnection());
        $this->assertInstanceOf('Analogue\ORM\System\Query', $mapper->query());
        $this->assertInstanceOf('Analogue\ORM\System\Query', $mapper->getQuery());
        $this->assertInstanceOf('Analogue\ORM\System\Query', $mapper->globalQuery());
        $this->assertInstanceOf('Analogue\ORM\Entity', $mapper->newInstance());

    }

    public function testEntityNewInstanceHydration()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');
        $attributes = [
            'column1' => 1,
            'column2' => "2",
        ];
        $entity = $mapper->newInstance($attributes);
        $this->assertEquals($entity->getEntityAttributes(), $attributes);
    }

    public function testCustomCommand()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $mapper->addCustomCommand('AnalogueTest\App\CustomCommand');

        $this->assertEquals(true, $mapper->hasCustomCommand('customCommand'));
        $this->assertEquals('executed' , $mapper->customCommand($mapper->newInstance()));
    }

    public function testRedirectCallsOnNewQuery()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertEquals('entities', $mapper->getTable());
    }

}
