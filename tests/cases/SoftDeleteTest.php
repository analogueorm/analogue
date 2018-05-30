<?php

use Analogue\ORM\EntityMap;
use TestApp\Stubs\Foo;

class SoftDeleteTest extends AnalogueTestCase
{
    /** @test */
    public function we_can_use_soft_deletes_if_defined_on_entity_map()
    {
        $this->migrate('foos', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->datetime('deleted_at')->nullable();
        });

        $this->analogue->register(Foo::class, new class() extends EntityMap {
            public $softDeletes = true;
        });

        $foo = new Foo();
        $foo->name = 'Test';
        mapper($foo)->store($foo);
        $this->assertDatabaseHas('foos', [
            'name' => 'Test',
        ]);
        mapper($foo)->delete($foo);

        $this->assertNull(mapper($foo)->find($foo->id));
        $this->clearCache();
        $this->assertNull(mapper($foo)->find($foo->id));

        $foos = mapper($foo)->withTrashed()->get();
        $foo = $foos->first();
        mapper($foo)->restore($foo);
        $this->assertNotNull(mapper($foo)->find($foo->id));
    }
}
