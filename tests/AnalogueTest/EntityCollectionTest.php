<?php namespace AnalogueTest;

use Analogue\ORM\Entity;
use Analogue\ORM\EntityCollection as Collection;
use Illuminate\Support\Collection as IlluminateCollection;
use PHPUnit_Framework_TestCase;

class EntityCollectionTest extends PHPUnit_Framework_TestCase {

    public function testAddingEntitiesToCollection()
    {
        $c = new Collection();

        $e = new Entity;
        $f = new Entity;

        $c->add($e)->add($f);

        $this->assertEquals(array($e, $f), $c->all());
    }

    public function testConstructorRejectNonMappableItems()
    {
        $this->setExpectedException('InvalidArgumentException');
        $c = new Collection([1,2,3]);
    }

    public function testPushRejectNonMappableItems()
    {
        $this->setExpectedException('InvalidArgumentException');
        $c = new Collection();
        $c->push('string');
    }

    public function testPrependRejectNonMappableItems()
    {
        $this->setExpectedException('InvalidArgumentException');
        $c = new Collection();
        $c->prepend('string');
    }

    public function testArraySetRejectNonMappableItems()
    {
        $this->setExpectedException('InvalidArgumentException');
        $c = new Collection();
        $c[0] = 'string';
    }

    public function testGettingMaxItemsFromCollection()
    {
        $a = new Entity;
        $a->foo = 10;

        $b = new Entity;
        $b->foo = 20;
        
        $c = new Collection([$a,$b]);

        $this->assertEquals(20, $c->max('foo'));
    }


    public function testGettingMinItemsFromCollection()
    {
        $a = new Entity;
        $a->foo = 10;

        $b = new Entity;
        $b->foo = 20;
        
        $c = new Collection([$a,$b]);

        $this->assertEquals(10, $c->min('foo'));
    }


    public function testContainsIndicatesIfEntityInArray()
    {
        get_analogue();

        $entity1 = new Entity;
        $entity1->id = 1;
        $entity2 = new Entity;
        $entity2->id = 2;
        $entity3 = new Entity;
        $entity3->id = 3;
        
        $c = new Collection(array($entity1, $entity2));

        $this->assertTrue($c->contains($entity1));
        $this->assertTrue($c->contains($entity2));
        $this->assertFalse($c->contains($entity3));
    }

    public function testFindMethodFindsEntityById()
    {
        $entity3 = new Entity;
        $entity3->id = 3;

        $c = new Collection([$entity3]);

        $this->assertSame($entity3, $c->find(3));
        $this->assertSame('analogue', $c->find(2, 'analogue'));
    }


    public function testCollectionDictionaryReturnsEntityKeys()
    {
        get_analogue();

        $entity1 = new Entity;
        $entity1->id = 1;
        $entity2 = new Entity;
        $entity2->id = 2;
        $entity3 = new Entity;
        $entity3->id = 3;

        $c = new Collection(array($entity1, $entity2, $entity3));

        $this->assertEquals(array(1,2,3), $c->getEntityKeys());
    }

    
    public function testCollectionMergesWithGivenCollection()
    {
        $e1 = new Entity;
        $e1->id = 1;
        $e2 = new Entity;
        $e2->id = 2;
        $e3 = new Entity;
        $e3->id = 3;

        $c1 = new Collection([$e1, $e2]);
        $c2 = new Collection([$e2, $e3]);

        $this->assertEquals(new Collection(array($e1, $e2, $e3)), $c1->merge($c2));
    }


    public function testCollectionDiffsWithGivenCollection()
    {
       $e1 = new Entity;
        $e1->id = 1;
        $e2 = new Entity;
        $e2->id = 2;
        $e3 = new Entity;
        $e3->id = 3;

        $c1 = new Collection([$e1, $e2]);
        $c2 = new Collection([$e2, $e3]);

        $this->assertEquals(new Collection(array($e1)), $c1->diff($c2));
    }


    public function testCollectionIntersectsWithGivenCollection()
    {
        $e1 = new Entity;
        $e1->id = 1;
        $e2 = new Entity;
        $e2->id = 2;
        $e3 = new Entity;
        $e3->id = 3;

        $c1 = new Collection([$e1, $e2]);
        $c2 = new Collection([$e2, $e3]);

        $this->assertEquals(new Collection(array($e2)), $c1->intersect($c2));
    }
    

    public function testCollectionReturnsUniqueItems()
    {
        $e1 = new Entity;
        $e1->id = 1;
        $e2 = new Entity;
        $e2->id = 2;

        $c = new Collection(array($e1, $e2, $e2));

        $this->assertEquals(new Collection(array($e1, $e2)), $c->unique());
    }

    
    public function testLists()
    {
        $e1 = new Entity;
        $e1->uid = 'f';
        $e1->name = 'foo';
        $e2 = new Entity;
        $e2->uid = 'b';
        $e2->name = 'bar';

        $data = new Collection([$e1,$e2]);
        $this->assertEquals(new IlluminateCollection(['f' => 'foo', 'b' => 'bar']), $data->lists('name', 'uid'));
        $this->assertEquals(new IlluminateCollection(['foo', 'bar']), $data->lists('name'));
    }

    
    public function testOnlyReturnsCollectionWithGivenModelKeys()
    {
        $e1 = new Entity;
        $e1->id = 1;
        $e2 = new Entity;
        $e2->id = 2;
        $e3 = new Entity;
        $e3->id = 3;

        $c = new Collection([$e1,$e2,$e3]);

        $this->assertEquals(new Collection([$e1]), $c->only(1));
        $this->assertEquals(new Collection([$e2,$e3]), $c->only([2, 3]));
    }

    
    public function testExceptReturnsCollectionWithoutGivenModelKeys()
    {
        $e1 = new Entity;
        $e1->id = 1;
        $e2 = new Entity;
        $e2->id = '2';
        $e3 = new Entity;
        $e3->id = 3;

        $c = new Collection([$e1,$e2,$e3]);

        $this->assertEquals(new Collection(array($e1, $e3)), $c->except(2));
        $this->assertEquals(new Collection(array($e1)), $c->except(array(2, 3)));
    }
}
