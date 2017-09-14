<?php

use Analogue\ORM\System\Proxies\CollectionProxy;
use TestApp\Blog;
use TestApp\Tag;

class MorphToManyTest extends DomainTestCase
{
    /** @test */
    public function relationship_is_created_along_with_its_parent()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $tags = [
            new Tag('Tag 1'),
            new Tag('Tag 2'),
        ];
        $blog->tags = $tags;
        $mapper = $this->mapper($blog);

        $mapper->store($blog);

        $this->seeInDatabase('tags', [
            'text' => 'Tag 1',
        ]);
        $this->seeInDatabase('tags', [
            'text' => 'Tag 2',
        ]);
        $this->seeInDatabase('taggables', [
            'taggable_id'   => $blog->id,
            'taggable_type' => Blog::class,
        ]);
    }

    /** @test */
    public function relationship_can_be_eager_loaded()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $tags = [
            new Tag('Tag 1'),
            new Tag('Tag 2'),
        ];
        $blog->tags = $tags;
        $mapper = $this->mapper($blog);

        $mapper->store($blog);

        $loadedBlog = $mapper->with('tags')->whereId($blog->id)->first();

        $this->assertCount(2, $loadedBlog->tags);
        $this->assertNotInstanceOf(CollectionProxy::class, $loadedBlog->tags);
    }

    /** @test */
    public function relationship_can_be_lazy_loaded()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $tags = [
            new Tag('Tag 1'),
            new Tag('Tag 2'),
        ];
        $blog->tags = $tags;
        $mapper = $this->mapper($blog);
        $mapper->store($blog);
        $this->clearCache();

        $loadedBlog = $mapper->whereId($blog->id)->first();

        $this->assertCount(2, $loadedBlog->tags);
        $this->assertInstanceOf(CollectionProxy::class, $loadedBlog->tags);
    }
}
