<?php

use Analogue\ORM\System\Proxies\CollectionProxy;
use TestApp\Blog;
use TestApp\Comment;

class MorphManyTest extends DomainTestCase
{
    /** @test */
    public function relationship_is_created_along_with_its_parent()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $comments = [
            new Comment('Comment 1'),
            new Comment('Comment 2'),
        ];
        $blog->comments = $comments;
        $mapper = $this->mapper($blog);

        $mapper->store($blog);

        $this->seeInDatabase('comments', [
            'text'             => 'Comment 1',
            'commentable_id'   => $blog->id,
            'commentable_type' => Blog::class,
        ]);

        $this->seeInDatabase('comments', [
            'text'             => 'Comment 2',
            'commentable_id'   => $blog->id,
            'commentable_type' => Blog::class,
        ]);
    }

    /** @test */
    public function relationship_can_be_eager_loaded()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $comments = [
            new Comment('Comment 1'),
            new Comment('Comment 2'),
        ];
        $blog->comments = $comments;
        $mapper = $this->mapper($blog);
        $mapper->store($blog);
        $loadedBlog = $mapper->with('comments')->whereId($blog->id)->first();

        $this->assertCount(2, $loadedBlog->comments);
        $this->assertNotInstanceOf(CollectionProxy::class, $loadedBlog->comments);
    }

    /** @test */
    public function relationship_can_be_lazy_loaded()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $comments = [
            new Comment('Comment 1'),
            new Comment('Comment 2'),
        ];
        $blog->comments = $comments;
        $mapper = $this->mapper($blog);
        $mapper->store($blog);

        $this->clearCache();

        $loadedBlog = $mapper->whereId($blog->id)->first();

        $this->assertCount(2, $loadedBlog->comments);
        $this->assertInstanceOf(CollectionProxy::class, $loadedBlog->comments);
    }
}
