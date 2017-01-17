<?php

use TestApp\Blog;
use TestApp\Article;

class HasManyTest extends DomainTestCase
{

    /** @test */
    public function relationship_is_created_along_with_its_parent()
    {   
        $blog = analogue_factory(Blog::class)->make();
        $article1 = analogue_factory(Article::class)->make();
        $article2 = analogue_factory(Article::class)->make();

        $articles = [
        	$article1,
        	$article2,
        ];

        $blog->articles = $articles;

        $mapper = $this->mapper(Blog::class);
        $mapper->store($blog);

        $this->seeInDatabase('blogs', [
        	'title' => $blog->title,
        ]);
        $this->seeInDatabase('articles', [
        	'title' => $article1->title,
        	'slug' => $article1->slug,
        	'content' => $article1->content,
        ]);
        $this->seeInDatabase('articles', [
        	'title' => $article2->title,
        	'slug' => $article2->slug,
        	'content' => $article2->content,
        ]);
    }

    /** @test */
    public function relationship_can_be_eager_loaded()
    {   
    	list($blog, $article1, $article2) = $this->buildObjects();

        $mapper = $this->mapper(Blog::class);
        $mapper->store($blog);

        $loadedBlog = $mapper->query()->with('articles')->first();

        $this->assertCount(2, $loadedBlog->articles);
    }

    /** @test */
    public function relationship_can_be_lazy_loaded()
    {   
    	list($blog, $article1, $article2) = $this->buildObjects();

        $mapper = $this->mapper(Blog::class);
        $mapper->store($blog);

        $loadedBlog = $mapper->query()->first();

        $this->assertCount(2, $loadedBlog->articles);
    }

    protected function buildObjects()
    {
    	$blog = analogue_factory(Blog::class)->make();
        $article1 = analogue_factory(Article::class)->make();
        $article2 = analogue_factory(Article::class)->make();

        $articles = [
        	$article1,
        	$article2,
        ];

        $blog->articles = $articles;

        return [$blog, $article1, $article2];
    }
}
