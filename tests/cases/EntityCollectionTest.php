<?php

use TestApp\Blog;

class EntityCollectionTest extends DomainTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_convert_a_collection_to_array()
    {
        for ($x = 0; $x < 5; $x++) {
            $blog = $this->factoryCreateUid(Blog::class);
        }
        $mapper = $this->mapper(Blog::class);
        $blogs = $mapper->all();
        $this->assertCount(5, $blogs->toArray());
    }

    /** @test */
    public function we_can_convert_a_collection_to_json()
    {
        for ($x = 0; $x < 5; $x++) {
            $blog = $this->factoryCreateUid(Blog::class);
        }
        $mapper = $this->mapper(Blog::class);
        $blogs = $mapper->all();
        $json = $blogs->toJson();
        $this->assertCount(5, json_decode($json));
    }
}
