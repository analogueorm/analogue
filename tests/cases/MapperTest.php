<?php

use Analogue\ORM\System\Query;
use Illuminate\Support\Collection;
use TestApp\Blog;
use TestApp\DI\Foo;
use TestApp\User;

class MapperTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_store_a_single_object()
    {
        $user = $this->factoryMake(User::class);
        $mapper = $this->mapper($user);
        $mapper->store($user);
        $this->seeInDatabase('users', ['name' => $user->name]);
    }

    /** @test */
    public function we_can_store_an_array_of_objects()
    {
        $userA = $this->factoryMake(User::class);
        $userB = $this->factoryMake(User::class);
        $mapper = $this->mapper($userA);
        $mapper->store([$userA, $userB]);
        $this->seeInDatabase('users', ['name' => $userA->name]);
        $this->seeInDatabase('users', ['name' => $userB->name]);
    }

    /** @test */
    public function we_can_store_a_collection_of_objects()
    {
        $userA = $this->factoryMake(User::class);
        $userB = $this->factoryMake(User::class);
        $mapper = $this->mapper($userA);
        $mapper->store(new Collection([$userA, $userB]));
        $this->seeInDatabase('users', ['name' => $userA->name]);
        $this->seeInDatabase('users', ['name' => $userB->name]);
    }

    /** @test */
    public function we_cant_store_objects_of_mixed_types()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $mapper = $this->mapper($user);
        $this->expectException(\InvalidArgumentException::class);
        $mapper->store(new Collection([$user, $blog]));
    }

    /** @test */
    public function we_can_delete_a_single_object()
    {
        $user = $this->factoryCreate(User::class);
        $mapper = $this->mapper($user);
        $mapper->delete($user);
        $this->notSeeInDatabase('users', ['name' => $user->name]);
    }

    /** @test */
    public function we_can_delete_multiple_objects()
    {
        $userA = $this->factoryCreate(User::class);
        $userB = $this->factoryCreate(User::class);
        $mapper = $this->mapper($userA);

        $mapper->delete([$userA, $userB]);
        $this->notSeeInDatabase('users', ['name' => $userA->name]);
        $this->notSeeInDatabase('users', ['name' => $userB->name]);
    }

    /** @test */
    public function deleting_an_object_retains_its_id_on_entity()
    {
        $userA = $this->factoryCreate(User::class);
        $mapper = $this->mapper($userA);
        $mapper->delete($userA);
        $this->assertNotNull($userA->id);
    }

    /** @test */
    public function we_can_get_a_query_builder()
    {
        $mapper = $this->mapper(User::class);
        $this->assertInstanceOf(Query::class, $mapper->query());
    }

    /** @test */
    public function we_can_apply_a_custom_scope()
    {
        //
    }

    /** @test */
    public function we_can_query_without_a_global_scope_if_one_is_applied()
    {
        //
    }

    /** @test */
    public function we_can_register_a_custom_event()
    {
        //
    }

    /** @test */
    public function we_can_register_and_call_custom_command()
    {
        //
    }

    /** @test */
    public function we_can_instantiate_an_entity_with_ioc_container()
    {
        $this->analogue->registerMapNamespace("TestApp\DI");
        $fooMapper = $this->mapper(Foo::class);
        $instance = $fooMapper->newInstance();
        $this->assertInstanceOf(Foo::class, $instance);
        $this->assertEquals(23, $instance->getBarValue());
    }
}
