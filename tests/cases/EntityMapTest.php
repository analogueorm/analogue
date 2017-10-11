<?php

use Illuminate\Contracts\Cache\Repository;
use TestApp\User;

class EntityMapTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_serialize_an_entity_map()
    {
        $mapper = $this->mapper(User::class);
        $entityMap = $mapper->getEntityMap();
        $serialized = serialize($entityMap);
        $this->assertEquals($entityMap, unserialize($serialized));
    }

    /** @test */
    public function an_entity_map_is_cached_after_instantiation()
    {
        $mapper = $this->mapper(User::class);
        $cache = $this->app->make(Repository::class);
        $this->assertTrue($cache->has(User::class));
    }
}
