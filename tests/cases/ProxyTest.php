<?php

use ProxyManager\Proxy\ProxyInterface;
use TestApp\Blog;
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
}
