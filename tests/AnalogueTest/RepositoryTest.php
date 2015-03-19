<?php namespace AnalogueTest;

use PHPUnit_Framework_TestCase;
use Analogue\ORM\Repository;
use Analogue\ORM\Entity;
use AnalogueTest\App\Permission;

class RepositoryTest extends PHPUnit_Framework_TestCase {

    protected function getRepository()
    {
        $analogue = get_analogue();
        return $analogue->repository('AnalogueTest\App\Permission');
    }

    public function testRepositoryInstantiation()
    {
        $analogue = get_analogue();
        $entity = new Entity;
        $repo = new Repository($analogue->mapper($entity));
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
        $repo = new Repository($entity);
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
        $repo = new Repository(get_class($entity));
        $this->assertInstanceOf('Analogue\ORM\Repository', $repo);
    }

    public function testStoreThenFind()
    {
        $repo = $this->getRepository();
        $p = new Permission('Puzzle');
        $repo->store($p);
        $id=$p->id;
        $this->assertGreaterThan(0,$id);
        $q = $repo->find($id);
        $this->assertEquals('Puzzle', $q->label);
    }

    public function testFirstMatch()
    {
        $repo = $this->getRepository();
        $p = new Permission('First');
        $q = new Permission('Second');
        $repo->store([$p,$q]);
        $id = $q->id;
        $r = $repo->firstMatching(['label' => 'Second']);
        $this->assertEquals('Second',$r->label);
        $this->assertEquals($id, $r->id);
    }


    public function testAllMatch()
    {
        $repo = $this->getRepository();
        $p = new Permission('Third');
        $q = new Permission('Third');
        $repo->store([$p,$q]);
        $r = $repo->allMatching(['label' => 'Third']);
        
        $this->assertInstanceOf('Analogue\ORM\EntityCollection',$r);
        $this->assertEquals(2, $r->count());
    }

    public function testPaginate()
    {
        $repo = $this->getRepository();
        $p = new Permission('First');
        $q = new Permission('Second');
        $repo->store([$p,$q]);
        
        $s = $repo->paginate(1);
        $this->assertEquals(1,count($s));
    }

    public function testStoreThenDelete()
    {
        $repo = $this->getRepository();
        $p = new Permission('Trash');
        $repo->store($p);
        $id=$p->id;
        $this->assertGreaterThan(0,$id);
        $repo->delete($p);
        $q = $repo->find($id);
        $this->assertNull($q);
    }
}
