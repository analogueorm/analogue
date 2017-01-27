<?php

use TestApp\Blog;
use TestApp\User;

class HasOneTest extends DomainTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_store_a_related_entity()
    {
        $user = $this->factoryCreateUid(User::class);
        $blog = $this->factoryMakeUid(Blog::class);
        $mapper = $this->mapper($user);
        $user->blog = $blog;
        $mapper->store($user);
        $this->seeInDatabase('blogs', ['user_id' => $user->id]);
        $this->seeInDatabase('blogs', ['id' => $blog->id]);
    }

    /** @test */
    public function storing_a_related_entity_updates_its_id()
    {
        $user = $this->factoryCreateUid(User::class);
        $blog = $this->factoryMakeUid(Blog::class);
        $mapper = $this->mapper($user);
        $user->blog = $blog;
        $mapper->store($user);
        $this->assertEquals($blog->id, $user->blog->id);
    }
}
