<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Repository;
use Analogue\ORM\Entity;

class RepositoryTest extends PHPUnit_Framework_TestCase {

    public function testRepositoryInstantiation()
    {
        $analogue = get_analogue();

        $entity = new Entity;

        $repo = new Repository($analogue->mapper($entity));
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);

        $repo = new Repository($entity);
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
        
    }

    public function testMapperInit()
    {
        $mapper = get_mapper('Analogue\ORM\Entity');

        $this->assertEquals(get_class($mapper), 'Analogue\ORM\System\Mapper');
    }
}
