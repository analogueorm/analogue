<?php

use Analogue\ORM\Exceptions\EntityMapNotFoundException;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\System\Mapper;
use TestApp\Identity;
use TestApp\NonMappedEntity;
use TestApp\User;

class ManagerTest extends AnalogueTestCase
{
    /** @test */
    public function analogue_is_registered()
    {
        $this->assertEquals(get_class($this->analogue), 'Analogue\ORM\System\Manager');
    }

    /** @test */
    public function we_cant_register_a_non_extisting_class()
    {
        $this->expectException(MappingException::class);
        $this->analogue->mapper(\Some\Entity::class);
    }

    /** @test */
    public function we_cant_register_without_a_map_in_strict_mode()
    {
        $this->assertFalse($this->analogue->isRegisteredEntity(NonMappedEntity::class));
        $this->expectException(EntityMapNotFoundException::class);
        $this->analogue->register(NonMappedEntity::class);
    }

    /** @test */
    public function we_can_register_without_a_map_in_non_strict_mode()
    {
        $this->analogue->setStrictMode(false);
        $this->assertFalse($this->analogue->isRegisteredEntity(NonMappedEntity::class));
        $mapper = $this->analogue->mapper(NonMappedEntity::class);
        $this->assertInstanceOf(Mapper::class, $mapper);
    }

    /** @test */
    public function we_cant_register_the_same_entity_twice()
    {
        $this->analogue->setStrictMode(false);
        $this->analogue->register(NonMappedEntity::class);
        $this->expectException(MappingException::class);
        $this->analogue->register(NonMappedEntity::class);
    }

    /** @test */
    public function multiple_mapper_calls_return_the_same_mapper()
    {
        $this->analogue->setStrictMode(false);
        $this->analogue->register(NonMappedEntity::class);
        $mapperA = $this->analogue->mapper(NonMappedEntity::class);
        $mapperB = $this->analogue->mapper(NonMappedEntity::class);
        $this->assertEquals(spl_object_hash($mapperA), spl_object_hash($mapperB));
    }

    /** @test */
    public function we_can_register_a_custom_namespace_for_maps()
    {
        $this->analogue->setStrictMode(true);
        $this->assertFalse($this->analogue->isRegisteredEntity(User::class));
        $this->analogue->registerMapNamespace("TestApp\Maps");
        $this->analogue->register(User::class);
        $mapper = $this->analogue->mapper(User::class);
        $this->assertInstanceOf(Mapper::class, $mapper);
    }

    /** @test */
    public function we_can_register_a_value_object()
    {
        $this->analogue->registerMapNamespace("TestApp\Maps");
        $this->analogue->registerValueObject(Identity::class);
        $this->assertFalse($this->analogue->isRegisteredEntity(Identity::class));
        $this->assertTrue($this->analogue->isRegisteredValueObject(Identity::class));
    }

    /** @test */
    public function we_cant_instantiate_a_mapper_from_a_value_object()
    {
        $this->analogue->registerMapNamespace("TestApp\Maps");
        $this->analogue->setStrictMode(false);
        $identity = new Identity('michael', 'jackson');
        $this->expectException(MappingException::class);
        $mapper = $this->analogue->mapper($identity);
    }

    /** @test */
    public function we_can_register_with_an_object_instance()
    {
        $this->analogue->registerMapNamespace("TestApp\Maps");
        $user = new User();
        $this->analogue->register($user);
    }

    /** @test */
    public function we_can_get_a_mapper_from_an_object_instance()
    {
        $this->analogue->registerMapNamespace("TestApp\Maps");
        $user = $this->factoryMake(User::class);
        $mapper = $this->analogue->mapper($user);
        $this->assertInstanceOf(Mapper::class, $mapper);
    }

    /** @test */
    public function we_can_register_a_custom_plugin()
    {
        //
    }
}
