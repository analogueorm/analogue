<?php

use TestApp\Blog;
use TestApp\User;

class BelongsToTest extends AnalogueTestCase 
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_store_a_related_entity()
    {
        $blog = $this->factoryCreateUid(Blog::class);
        $user = $this->factoryMakeUid(User::class);
        $mapper = $this->mapper($blog);
        $blog->user = $user;
        $mapper->store($blog);
        $this->assertEquals($user->id, $blog->user_id);
        $this->seeInDatabase('blogs', ['user_id' => $user->id]);
    }  


}