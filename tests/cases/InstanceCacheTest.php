<?php

use TestApp\Blog;
use TestApp\User;

class InstanceCacheTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function same_entity_is_return_when_the_same_record_is_loaded_twice()
    {
        $user = $this->factoryMake(User::class);
        $this->mapper($user)->store($user);
        $this->clearCache();
        $load1 = $this->mapper($user)->find($user->id);
        $load2 = $this->mapper($user)->find($user->id);
        $this->assertEquals(spl_object_hash($load1), spl_object_hash($load2));
    }

    /** @test */
    public function mapper_returns_same_object_when_a_related_object_is_loaded()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $user->blog = $blog;
        $this->mapper($user)->store($user);
        $this->clearCache();
        $this->assertGreaterThan(0, $user->id);
        $this->assertGreaterThan(0, $blog->id);

        $loadedBlog = $this->mapper($blog)->find($blog->id);
        $loadedUser = $this->mapper($user)->find($user->id);

        $this->assertEquals(spl_object_hash($loadedBlog), spl_object_hash($loadedUser->blog));
    }

    /** @test */
    public function mapper_returns_same_instance_when_a_parent_relationship_is_loaded_from_a_proxy()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $user->blog = $blog;
        $this->mapper($user)->store($user);
        $this->clearCache();
        $this->assertGreaterThan(0, $user->id);
        $this->assertGreaterThan(0, $blog->id);

        $loadedUser = $this->mapper($user)->find($user->id);
        $id = $loadedUser->blog->user->id;
        $this->assertEquals($id, $user->id);

        $object = $this->getUnderlyingObject($loadedUser->blog->user);
        $this->assertEquals(spl_object_hash($loadedUser), spl_object_hash($object));
    }

    /** @test */
    public function loading_a_freshly_stored_object_returns_original_instance()
    {
        $user = $this->factoryMake(User::class);
        $this->mapper($user)->store($user);
        $loadedUser = $this->mapper($user)->find($user->id);
        $this->assertEquals(spl_object_hash($user), spl_object_hash($loadedUser));
    }

    /** @test */
    public function loading_a_freshly_deleted_object_returns_null()
    {
        $user = $this->factoryMake(User::class);
        $this->mapper($user)->store($user);
        $this->clearCache();
        $this->mapper($user)->delete($user);
        $this->assertNull($this->mapper($user)->find($user->id));
    }

    protected function getUnderlyingObject($proxy)
    {
        $reflect = new ReflectionClass($proxy);
        $props = $reflect->getProperties(ReflectionProperty::IS_PRIVATE);

        foreach ($props as $prop) {
            $name = $prop->getName();
            if (starts_with($prop->getName(), 'valueHolder')) {
                $prop->setAccessible(true);

                return $prop->getValue($proxy);
            }
        }
        $this->assertFalse(true);
    }
}
