<?php

use Analogue\ORM\EntityMap;
use TestApp\Stubs\Foo;

class EntityCacheTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function all_attributes_are_cached()
    {
        $this->migrate('foos', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->analogue->register(Foo::class, new class() extends EntityMap {
            public $timestamps = true;
        });

        $foo = new Foo();
        $foo->name = 'test';

        $mapper = mapper(Foo::class);
        $mapper->store($foo);

        $this->clearCache();

        $loadedFoo = $mapper->find($foo->id);

        $this->assertEquals(
            $loadedFoo->getEntityAttributes(),
            $mapper->getEntityCache()->get($foo->id)
        );
    }
}
