<?php

use Analogue\ORM\EntityCollection;
use Analogue\ORM\Exceptions\EntityNotFoundException;
use TestApp\Blog;
use TestApp\User;

class QueryTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function test_has_query()
    {
        $blog = $this->factoryCreateUid(Blog::class);
        $userWithBlog = $this->factoryMakeUid(User::class);
        $blog->user = $userWithBlog;
        $userWithoutBlog = $this->factoryMakeUid(User::class);
        $userMapper = $this->mapper($userWithoutBlog);
        $userMapper->store($userWithoutBlog);
        $blogMapper = $this->mapper($blog);
        $blogMapper->store($blog);
        $this->clearCache();

        $users = $userMapper->has('blog')->get();
        $this->assertCount(1, $users);
        $this->assertEquals($userWithBlog->id, $users->first()->id);
    }

    /** @test */
    public function test_where_has_query()
    {
        $blogA = $this->factoryCreateUid(Blog::class);
        $blogB = $this->factoryCreateUid(Blog::class);

        $userA = $this->factoryCreateUid(User::class);
        $userB = $this->factoryCreateUid(User::class);

        $userA->blog = $blogA;
        $userB->blog = $blogB;

        $userMapper = $this->mapper(User::class);
        $userMapper->store([$userA, $userB]);

        $this->clearCache();

        $users = $userMapper->whereHas('blog', function ($query) use ($blogA) {
            return $query->whereId($blogA->id);
        })->get();
        $this->assertCount(1, $users);
        $this->assertEquals($userA->id, $users->first()->id);
    }

    /** @test */
    public function test_find_query()
    {
        $blogA = $this->factoryCreateUid(Blog::class);
        $this->clearCache();
        $mapper = $this->mapper(Blog::class);
        $blog = $mapper->find($blogA->id);
        $this->assertInstanceOf(Blog::class, $blog);
        $this->assertEquals($blogA->id, $blog->id);
    }

    /** @test */
    public function test_findMany_query()
    {
        $blogA = $this->factoryCreateUid(Blog::class);
        $blogB = $this->factoryCreateUid(Blog::class);
        $this->clearCache();
        $mapper = $this->mapper(Blog::class);
        $blogs = $mapper->findMany([$blogA->id, $blogB->id]);
        $this->assertInstanceOf(EntityCollection::class, $blogs);
        $this->assertCount(2, $blogs);
    }

    /** @test */
    public function test_findOrFail_query()
    {
        $mapper = $this->mapper(Blog::class);
        $this->expectException(EntityNotFoundException::class);
        $mapper->findOrFail('1234');
    }

    /** @test */
    public function test_pluck()
    {
        $blogA = analogue_factory(Blog::class)->create(['id' => 1]);
        $blogB = analogue_factory(Blog::class)->create(['id' => 2]);
        $this->clearCache();
        $mapper = $this->mapper(Blog::class);

        $ids = $mapper->orderBy('id')->pluck('id');

        $this->assertEquals([$blogA->id, $blogB->id], $ids->all());
    }

    public function test_alias()
    {
        $user = $this->factoryCreateUid(User::class);
        /** @var \Analogue\ORM\System\Mapper $userMapper */
        $userMapper = $this->mapper($user);
        $userMapper->store($user);
        $userTable = $userMapper->getEntityMap()->getTable();
        $primaryKey = $userMapper->getEntityMap()->getKeyName();
        $this->clearCache();

        $query = $userMapper->getQuery();
        $query->select(["$userTable.$primaryKey as $primaryKey", 'identity_firstname', 'identity_lastname']);
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }
}
