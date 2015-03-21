<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Entity;
use AnalogueTest\App\CustomCommand;
use AnalogueTest\App\Resource;

class MapperTest extends PHPUnit_Framework_TestCase {

    public function testMapperFactory()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertInstanceOf('Analogue\ORM\System\Mapper', $mapper);
        $this->assertInstanceOf('Analogue\ORM\EntityMap', $mapper->getEntityMap());
        $this->assertInstanceOf('Analogue\ORM\System\EntityCache', $mapper->getEntityCache());
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

    public function testStoreAndHydrateAllColumnTypes()
    {
        $r = new Resource('columns');
        $string = ' test ';
        $boolean = true;
        $integer = 10;
        $date = \Carbon\Carbon::now();
        $dateTime = \Carbon\Carbon::now();
        $time = \Carbon\Carbon::now();
        $decimal = 12345.12;
        $double = 1234512.12345;
        $enum = 'a';
        $float = 0.12345;
        $json = json_encode(['a' => '1', 'b' => '2']);
        $r->foo_string = $string;
        $r->foo_boolean= $boolean;
        $r->foo_integer =$integer;
        $r->foo_date = $date;
        $r->foo_dateTime = $dateTime;
        $r->foo_time = $time;
        $r->foo_decimal = $decimal;
        $r->foo_double = $double;
        $r->foo_enum = $enum;
        $r->foo_float = $float;
        $r->foo_json = $json;
        $m=get_mapper($r);
        $m->store($r);
        $q=$m->whereName('columns')->first();
        
        $this->assertEquals($string, $q->getEntityAttribute('foo_string'));
        $this->assertEquals($boolean, $q->getEntityAttribute('foo_boolean'));
        $this->assertEquals($integer, $q->getEntityAttribute('foo_integer'));
        $this->assertEquals($date, $q->getEntityAttribute('foo_date'));
        $this->assertEquals($dateTime, $q->getEntityAttribute('foo_dateTime'));
        $this->assertEquals($time, $q->getEntityAttribute('foo_time'));
        $this->assertEquals($decimal, $q->getEntityAttribute('foo_decimal'));
        $this->assertEquals($double, $q->getEntityAttribute('foo_double'));
        $this->assertEquals($enum, $q->getEntityAttribute('foo_enum'));
        $this->assertEquals($float, $q->getEntityAttribute('foo_float'));
        $this->assertEquals($json, $q->getEntityAttribute('foo_json'));
    }
}
