<?php

use Illuminate\Support\Collection;
use TestApp\Article;
use TestApp\Blog;
use TestApp\Group;
use TestApp\User;

class AggregateTest extends AnalogueTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->analogue->registerMapNamespace("TestApp\Maps");
    }

    /** @test */
    public function we_can_use_custom_id()
    {
        $user = $this->factoryMake(User::class);
        $id = $this->randId();
        $user->id = $id;

        $this->mapper($user)->store($user);
        $this->seeInDatabase('users', ['id' => $id]);
    }

    /** @test */
    public function we_can_store_a_related_entity()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $user->blog = $blog;
        $this->mapper($user)->store($user);
        $this->assertGreaterThan(0, $user->id);
        $this->assertGreaterThan(0, $blog->id);
        $this->seeInDatabase('blogs', ['id' => $blog->id]);
    }

    /** @test */
    public function we_can_store_an_inverse_related_entity()
    {
        $blog = $this->factoryMakeUid(Blog::class);
        $mapper = $this->mapper($blog);
        $user = $this->factoryMakeUid(User::class);
        $blog->user = $user;
        $mapper->store($blog);
        $this->seeInDatabase('blogs', ['id' => $blog->id, 'user_id' => $user->id]);
    }

    /** @test */
    public function we_can_store_a_related_entity_on_an_loaded_entity()
    {
        $user = $this->factoryCreateUid(User::class);
        $blog = $this->factoryCreateUid(Blog::class);
        $id = $user->id;
        $mapper = $this->mapper($user);
        $loadedUser = $mapper->find($id);
        $loadedUser->blog = $blog;

        $mapper->store($loadedUser);
        $this->seeInDatabase('blogs', ['user_id' => $user->id]);
    }

    /** @test */
    public function we_can_store_a_related_entity_with_a_custom_id()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $id = $this->randId();
        $blog->id = $id;
        $user->blog = $blog;
        $this->mapper($user)->store($user);
        $this->seeInDatabase('blogs', ['id' => $id]);
    }

    /** @test */
    public function we_can_store_a_related_collection()
    {
        $article1 = $this->factoryMake(Article::class);
        $article2 = $this->factoryMake(Article::class);
        $blog = $this->factoryMake(Blog::class);
        $blog->articles = new Collection([$article1, $article2]);
        $mapper = $this->mapper(Blog::class);
        $mapper->store($blog);
        $this->assertGreaterThan(0, $blog->id);
        $this->assertGreaterThan(0, $article1->id);
        $this->assertGreaterThan(0, $article2->id);
    }

    /** @test */
    public function we_can_store_a_related_collection_with_custom_ids()
    {
        $article1 = $this->factoryMake(Article::class);
        $article2 = $this->factoryMake(Article::class);
        $id1 = $this->randId();
        $id2 = $this->randId();
        $article1->id = $id1;
        $article2->id = $id2;
        $blog = $this->factoryMake(Blog::class);
        $blog->articles = new Collection([$article1, $article2]);
        $mapper = $this->mapper(Blog::class);
        $mapper->store($blog);
        $this->assertGreaterThan(0, $blog->id);
        $this->assertEquals($id1, $article1->id);
        $this->assertEquals($id2, $article2->id);
        $this->seeInDatabase('articles', ['id' => $id1]);
        $this->seeInDatabase('articles', ['id' => $id2]);
    }

    /** @test */
    public function we_can_recursively_store_related_entity()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryMake(Blog::class);
        $article = $this->factoryMake(Article::class);

        $blog->articles = new Collection([$article]);
        $user->blog = $blog;
        $mapper = $this->mapper(User::class);
        $mapper->store($user);

        $this->assertGreaterThan(0, $user->id);
        $this->assertGreaterThan(0, $blog->id);
        $this->assertGreaterThan(0, $article->id);
    }

    /** @test */
    public function we_can_store_an_existing_entity_as_relation()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryCreate(Blog::class);
        $user->blog = $blog;
        $this->mapper($user)->store($user);
        $this->seeInDatabase('blogs', ['user_id' => $user->id]);
    }

    /** @test */
    public function related_entity_with_dirty_attributes_will_update()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryCreate(Blog::class);
        $user->blog = $blog;
        $blog->title = 'New Title';
        $this->mapper($user)->store($user);
        $this->seeInDatabase('blogs', ['title' => 'New Title']);
    }

    /** @test */
    public function related_entity_with_dirty_attributes_will_update_recursively()
    {
        $user = $this->factoryMakeUid(User::class);
        $blog = $this->factoryMakeUid(Blog::class);
        $article = $this->factoryMake(Article::class);
        $blog->articles = new Collection([$article]);
        $user->blog = $blog;
        $mapper = $this->mapper(User::class);
        $mapper->store($user);
        $article->title = 'New Title';
        $mapper->store($user);
        $this->seeInDatabase('articles', ['title' => 'New Title']);
    }

    /** @test */
    public function related_collection_with_dirty_entities_are_updated_when_lazy_loaded()
    {
        $user = $this->factoryMakeUid(User::class);
        $group1 = $this->factoryMakeUid(Group::class);
        $group2 = $this->factoryMakeUid(Group::class);
        $user->groups = [$group1, $group2];
        $mapper = $this->mapper(User::class);
        $mapper->store($user);
        $this->seeInDatabase('groups_users', ['user_id' => $user->id, 'group_id' => $group1->id]);
        $this->seeInDatabase('groups_users', ['user_id' => $user->id, 'group_id' => $group2->id]);

        $this->clearCache();

        $loadedUser = $mapper->find($user->id);
        $loadedUser->groups->first()->name = 'New Group Name';
        $mapper->store($loadedUser);
        $this->seeInDatabase('groups', ['name' => 'New Group Name']);
    }

    /** @test */
    public function related_collection_with_dirty_entities_are_updated_when_eager_loaded()
    {
        $user = $this->factoryMakeUid(User::class);
        $group1 = $this->factoryMakeUid(Group::class);
        $group2 = $this->factoryMakeUid(Group::class);
        $user->groups = [$group1, $group2];
        $mapper = $this->mapper(User::class);
        $mapper->store($user);
        $this->seeInDatabase('groups_users', ['user_id' => $user->id, 'group_id' => $group1->id]);
        $this->seeInDatabase('groups_users', ['user_id' => $user->id, 'group_id' => $group2->id]);

        $this->clearCache();

        $loadedUser = $mapper->with('groups')->whereId($user->id)->first();
        $loadedUser->groups->first()->name = 'New Group Name';
        $mapper->store($loadedUser);
        $this->seeInDatabase('groups', ['name' => 'New Group Name']);
    }

    /** @test */
    public function setting_null_on_related_attribute_will_detach_relation()
    {
        $user = $this->factoryMake(User::class);
        $blog = $this->factoryCreate(Blog::class, ['id' => $this->randId()]);
        $user->blog = $blog;
        $this->mapper($user)->store($user);
        $user->blog = null;
        $this->mapper($user)->store($user);
        $this->seeInDatabase('blogs', ['title' => $blog->title, 'user_id' => null]);
    }
}
