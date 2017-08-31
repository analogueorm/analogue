<?php

use ProxyManager\Proxy\LazyLoadingInterface;
use TestApp\Blog;
use TestApp\Comment;

class MorphOneTest extends DomainTestCase
{
    /** @test */
    public function relationship_is_created_along_with_its_parent()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $comment = new Comment('Comment 1');

        $blog->topComment = $comment;
        $mapper = $this->mapper($blog);

        $mapper->store($blog);

        $this->seeInDatabase('comments', [
            'text'             => 'Comment 1',
            'commentable_id'   => $blog->id,
            'commentable_type' => Blog::class,
        ]);
    }

    /** @test */
    public function relationship_is_always_eager_loaded()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $comment = new Comment('Comment 1');

        $blog->topComment = $comment;
        $mapper = $this->mapper($blog);

        $mapper->store($blog);
        $loadedBlog = $mapper->find($blog->id);

        $this->assertNotInstanceOf(LazyLoadingInterface::class, $loadedBlog->topComment);
    }

    /** @test */
    public function relationship_is_set_null_if_no_related_entity_exists()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $mapper = $this->mapper($blog);
        $mapper->store($blog);
        $loadedBlog = $mapper->find($blog->id);
        $this->assertNull($blog->topComment);
    }
}
