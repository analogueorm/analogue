<?php

use ProxyManager\Proxy\ProxyInterface;
use TestApp\Article;
use TestApp\Blog;
use TestApp\Stubs\Foo;
use TestApp\Stubs\Bar;
use Analogue\ORM\EntityMap;
use Analogue\ORM\EntityCollection;

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
            'title'   => $article1->title,
            'slug'    => $article1->slug,
            'content' => $article1->content,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article2->title,
            'slug'    => $article2->slug,
            'content' => $article2->content,
        ]);
    }

    /** @test */
    public function relationship_isnt_updated_or_detached_if_no_attributes_are_dirty()
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

        $this->clearCache();

        $loadedBlog = $mapper->find($blog->id);
        $mapper->store($loadedBlog);
        $this->seeInDatabase('blogs', [
            'title' => $blog->title,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article1->title,
            'slug'    => $article1->slug,
            'content' => $article1->content,
            'blog_id' => $blog->id,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article2->title,
            'slug'    => $article2->slug,
            'content' => $article2->content,
            'blog_id' => $blog->id,
        ]);
    }


    /** @test */
    public function relationship_isnt_updated_or_detached_if_no_attributes_are_dirty_and_proxy_is_replaced_by_collection()
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

        $this->clearCache();

        $loadedBlog = $mapper->find($blog->id);
        $loadedBlog->articles = $loadedBlog->articles->map(function($a) { return $a; });

        $mapper->store($loadedBlog);
        $this->seeInDatabase('blogs', [
            'title' => $blog->title,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article1->title,
            'slug'    => $article1->slug,
            'content' => $article1->content,
            'blog_id' => $blog->id,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article2->title,
            'slug'    => $article2->slug,
            'content' => $article2->content,
            'blog_id' => $blog->id,
        ]);
    }

     /** @test */
    public function relationship_isnt_updated_or_detached_if_we_store_the_relation_from_its_child()
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

        $this->clearCache();

        $mapper = $this->mapper(Article::class);
        $article = $mapper->find($article1->id);
        $mapper->store($article);

        $this->seeInDatabase('blogs', [
            'title' => $blog->title,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article1->title,
            'slug'    => $article1->slug,
            'content' => $article1->content,
            'blog_id' => $blog->id,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article2->title,
            'slug'    => $article2->slug,
            'content' => $article2->content,
            'blog_id' => $blog->id,
        ]);
    }

    /** @test */
    public function relationship_isnt_updated_or_detached_if_the_proxy_is_loaded()
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

        $this->clearCache();

        
        $blog = $mapper->find($blog->id);
        $blog->articles->all();
        $mapper->store($blog);
        
        $this->seeInDatabase('blogs', [
            'title' => $blog->title,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article1->title,
            'slug'    => $article1->slug,
            'content' => $article1->content,
            'blog_id' => $blog->id,
        ]);
        $this->seeInDatabase('articles', [
            'title'   => $article2->title,
            'slug'    => $article2->slug,
            'content' => $article2->content,
            'blog_id' => $blog->id,
        ]);
    }


    /** @test */
    public function relationship_can_be_eager_loaded()
    {
        list($blog, $article1, $article2) = $this->buildObjects();

        $mapper = $this->mapper(Blog::class);
        $mapper->store($blog);
        setTddOn();
        $loadedBlog = $mapper->query()->with('articles')->first();

        $this->assertNotInstanceOf(ProxyInterface::class, $loadedBlog->articles);
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

    /** @test */
    public function relationship_is_not_overrided_to_null_when_using_custom_foreign_keys()
    {
        $this->migrate('foos', function($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function($table) {
            $table->increments('id');
            $table->integer('custom_id');
            $table->string('title');
        });

        $this->analogue->register(Foo::class, new class extends EntityMap {
            public function bars(Foo $foo)
            {
                return $this->hasMany($foo, Bar::class, 'custom_id', 'id');
            }
        });

        $this->analogue->register(Bar::class, new class extends EntityMap {});

        $foo = new Foo;
        $foo->title = "Test";
        $foo->bars = new EntityCollection;
        $bar1 = new Bar;
        $bar1->title = "Test1";
        $bar2 = new Bar;
        $bar2->title = "Test2";
        $foo->bars->add($bar1);
        $foo->bars->add($bar2);
        $mapper = $this->mapper(Foo::class);
        $mapper->store($foo);
        $this->seeInDatabase('foos', [
            "title" => "Test",
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test1",
            "custom_id" => $foo->id,
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test2",
            "custom_id" => $foo->id,
        ]);
        $this->clearCache();

    }

    /** @test */
    public function relationship_is_not_overrided_to_null_on_store_with_default_foreignkey()
    {
        $this->migrate('foos', function($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function($table) {
            $table->increments('id');
            $table->integer('foo_id');
            $table->string('title');
        });

        $this->analogue->register(Foo::class, new class extends EntityMap {
            public function bars(Foo $foo)
            {
                return $this->hasMany($foo, Bar::class, 'foo_id', 'id');
            }
        });

        $this->analogue->register(Bar::class, new class extends EntityMap {});

        $foo = new Foo;
        $foo->title = "Test";
        $foo->bars = new EntityCollection;
        $bar1 = new Bar;
        $bar1->title = "Test1";
        $bar2 = new Bar;
        $bar2->title = "Test2";
        $foo->bars->add($bar1);
        $foo->bars->add($bar2);
        $mapper = $this->mapper(Foo::class);
        $mapper->store($foo);

        $this->seeInDatabase('foos', [
            "title" => "Test",
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test1",
            "foo_id" => $foo->id,
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test2",
            "foo_id" => $foo->id,
        ]);
        $this->clearCache();

    }

     /** @test */
    public function relationship_is_not_overrided_to_null_when_using_custom_property_as_foreign_key()
    {
        $this->migrate('foos', function($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function($table) {
            $table->increments('id');
            $table->integer('prop1')->nullable();
            $table->string('title');
        });

        $this->analogue->register(Foo::class, new class extends EntityMap {
            public function bars(Foo $foo)
            {
                return $this->hasMany($foo, Bar::class, 'prop1', 'id');
            }
        });

        $this->analogue->register(Bar::class, new class extends EntityMap {
            protected $properties = [
                'prop1'
            ];  
        });

        $foo = new Foo;
        $foo->title = "Test";
        $foo->bars = new EntityCollection;
        $bar1 = new Bar;
        $bar1->title = "Test1";
        $bar2 = new Bar;
        $bar2->title = "Test2";
        $foo->bars->add($bar1);
        $foo->bars->add($bar2);

        $mapper = $this->mapper(Foo::class);
        $mapper->store($foo);
        
        $this->seeInDatabase('foos', [
            "title" => "Test",
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test1",
            "prop1" => $foo->id,
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test2",
            "prop1" => $foo->id,
        ]);
        $this->clearCache();

    }

      /** @test */
    public function relationship_is_not_overrided_to_null_when_using_property_as_foreign_key()
    {
        $this->migrate('foos', function($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function($table) {
            $table->increments('id');
            $table->integer('foo_id')->nullable();
            $table->string('title');
        });

        $this->analogue->register(Foo::class, new class extends EntityMap {
            public function bars(Foo $foo)
            {
                return $this->hasMany($foo, Bar::class, 'foo_id', 'id');
            }
        });

        $this->analogue->register(Bar::class, new class extends EntityMap {
            protected $properties = [
                'foo_id'
            ];  
        });

        $foo = new Foo;
        $foo->title = "Test";
        $foo->bars = new EntityCollection;
        $bar1 = new Bar;
        $bar1->title = "Test1";
        $bar2 = new Bar;
        $bar2->title = "Test2";
        $foo->bars->add($bar1);
        $foo->bars->add($bar2);

        $mapper = $this->mapper(Foo::class);
        $mapper->store($foo);
        
        $this->seeInDatabase('foos', [
            "title" => "Test",
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test1",
            "foo_id" => $foo->id,
        ]);
        $this->seeInDatabase('bars', [
            "title" => "Test2",
            "foo_id" => $foo->id,
        ]);
        $this->clearCache();

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
