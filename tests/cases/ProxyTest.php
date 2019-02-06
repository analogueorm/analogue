<?php

use Analogue\ORM\EntityCollection;
use Analogue\ORM\EntityMap;
use ProxyManager\Proxy\ProxyInterface;
use TestApp\Blog;
use TestApp\PlainProxy;
use TestApp\Stubs\Bar;
use TestApp\Stubs\Foo;
use TestApp\User;

class ProxyTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function proxies_are_setup_by_default()
    {
        $user = $this->factoryCreateUid(User::class);
        $this->assertNull($user->blog);
        $this->assertInstanceOf(Analogue\ORM\System\Proxies\CollectionProxy::class, $user->getEntityAttribute('articles'));
        $this->assertInstanceOf(Illuminate\Support\Collection::class, $user->getEntityAttribute('articles'));
        $this->assertInstanceOf(ProxyInterface::class, $user->getEntityAttribute('articles'));
    }

    /** @test */
    public function we_can_load_a_relationship_from_a_proxy()
    {
        $blog = $this->factoryCreateUid(Blog::class);
        $user = $this->factoryCreateUid(User::class);
        $user->blog = $blog;
        $mapper = $this->mapper($user);
        $mapper->store($user);
        $loadedUser = $mapper->find($user->id);
        $this->assertEquals($blog->id, $loadedUser->blog->id);
    }

    /** @test */
    public function proxies_are_set_on_plain_object_class_properties()
    {
        $user = $this->factoryCreateUid(User::class);
        $proxy = new PlainProxy($user, $user);
        $mapper = $this->mapper($proxy);
        $proxy = $mapper->store($proxy);

        $this->clearCache();

        $loadedProxy = $mapper->find($proxy->getId());
        $this->assertInstanceOf(ProxyInterface::class, $loadedProxy->getRelated());
    }

    /** @test */
    public function proxies_are_set_on_relationship_with_additionnal_methods()
    {
        $this->migrate('foos', function ($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function ($table) {
            $table->increments('id');
            $table->integer('custom_id');
            $table->string('title');
        });

        $this->analogue->register(Foo::class, new class() extends EntityMap {
            public function bars(Foo $foo)
            {
                return $this->hasMany($foo, Bar::class, 'custom_id', 'id')->orderBy('title', 'desc');
            }
        });

        $this->analogue->register(Bar::class, new class() extends EntityMap {
        });

        $foo = new Foo();
        $foo->title = 'Test';
        $foo->bars = new EntityCollection();
        $bar1 = new Bar();
        $bar1->title = 'ZYX';
        $bar2 = new Bar();
        $bar2->title = 'ABC';
        $foo->bars->add($bar1);
        $foo->bars->add($bar2);
        $mapper = $this->mapper(Foo::class);
        $mapper->store($foo);
        $this->seeInDatabase('foos', [
            'title' => 'Test',
        ]);
        $this->seeInDatabase('bars', [
            'title'     => 'ZYX',
            'custom_id' => $foo->id,
        ]);
        $this->seeInDatabase('bars', [
            'title'     => 'ABC',
            'custom_id' => $foo->id,
        ]);
        $this->clearCache();
        $loadedFoo = $mapper->find($foo->id);
        $this->assertInstanceof(Analogue\ORM\System\Proxies\CollectionProxy::class, $loadedFoo->bars);
        $this->assertEquals('ZYX', $loadedFoo->bars->first()->title);
    }

    /** @test */
    public function many_relationships_within_the_with_property_are_always_eager_loaded()
    {
        $this->migrate('foos', function ($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function ($table) {
            $table->increments('id');
            $table->integer('foo_id');
            $table->string('title');
        });
        $this->analogue->register(Foo::class, new class() extends EntityMap {
            protected $with = ['bars'];

            public function bars(Foo $foo)
            {
                return $this->hasMany($foo, Bar::class, 'food_id', 'id');
            }
        });
        $this->analogue->register(Bar::class, new class() extends EntityMap {
        });
        $foo = new Foo();
        $foo->title = 'Test';
        mapper($foo)->store($foo);
        $this->clearCache();
        $loadedFoo = mapper($foo)->find($foo->id);
        $this->assertNotNull($loadedFoo);
        $this->assertInstanceOf(Illuminate\Support\Collection::class, $loadedFoo->bars);
        $this->assertNotInstanceOf(Analogue\ORM\System\Proxies\CollectionProxy::class, $loadedFoo->bars);
    }

    /** @test */
    public function single_relationships_within_the_with_property_are_always_eager_loaded()
    {
        $this->migrate('foos', function ($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->migrate('bars', function ($table) {
            $table->increments('id');
            $table->integer('foo_id')->nullable();
            $table->string('title');
        });
        $this->analogue->register(Foo::class, new class() extends EntityMap {
        });
        $this->analogue->register(Bar::class, new class() extends EntityMap {
            protected $with = ['foo'];

            public function foo(Bar $bar)
            {
                return $this->belongsTo($bar, Foo::class);
            }
        });
        $bar = new Bar();
        $bar->title = 'Test';
        $bar->foo = new Foo();
        $bar->foo->title = 'test';
        mapper($bar)->store($bar);
        $this->clearCache();
        $loadedBar = mapper($bar)->find($bar->id);
        $this->assertNotNull($loadedBar);
        $this->assertInstanceOf(Foo::class, $loadedBar->foo);
        $this->assertNotInstanceOf(\ProxyManager\Proxy\LazyLoadingInterface::class, $loadedBar->foo);
        $bar = new Bar();
        $bar->title = 'Test';
        mapper($bar)->store($bar);
        $this->clearCache();
        $loadedBar = mapper($bar)->find($bar->id);
        $this->assertNotNull($loadedBar);
        $this->assertNull($loadedBar->foo);
    }
}
