<?php

use ProxyManager\Proxy\ProxyInterface;
use TestApp\Blog;
use TestApp\PlainProxy;
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
        $loadedProxy = $mapper->find($proxy->getId());
        $this->assertInstanceOf(ProxyInterface::class, $loadedProxy->getRelated());
    }
}
