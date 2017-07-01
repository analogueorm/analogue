<?php

use Illuminate\Support\Collection;
use TestApp\Group;
use TestApp\User;
use TestApp\CustomUser;
use TestApp\CustomGroup;

class BelongsToManyTest extends DomainTestCase
{
    /** @test */
    public function we_can_store_a_many_to_many_relationship()
    {
        $user = $this->factoryMakeUid(User::class);
        $group = new Group();
        $group->id = $this->randId();
        $group->name = 'test group';
        $user->groups->push($group);
        $mapper = $this->mapper($user);
        $mapper->store($user);
        $this->seeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);
        $this->seeInDatabase('groups', [
            'name' => 'test group',
            'id'   => $group->id,
        ]);
    }

    /** @test */
    public function we_can_retrieve_a_many_to_many_relationship()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $this->assertCount(3, $user->groups);
        foreach ($user->groups as $group) {
            $this->assertInstanceOf(Group::class, $group);
        }
    }

    /** @test */
    public function we_can_store_several_entities_in_a_many_to_many_relationship()
    {
        $user = $this->factoryMakeUid(User::class);
        $groupA = new Group();
        $groupA->id = $this->randId();
        $groupA->name = 'test group A';
        $groupB = new Group();
        $groupB->id = $this->randId();
        $groupB->name = 'test group B';
        $user->groups->push($groupA);
        $user->groups->push($groupB);
        $mapper = $this->mapper($user);
        $mapper->store($user);
        $this->seeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $groupA->id,
        ]);
        $this->seeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $groupB->id,
        ]);
        $this->seeInDatabase('groups', [
            'id'   => $groupA->id,
            'name' => 'test group A',
        ]);
        $this->seeInDatabase('groups', [
            'id'   => $groupB->id,
            'name' => 'test group B',
        ]);
    }

    /** @test */
    public function we_can_add_existing_items_to_a_many_to_many_relationship()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $group = $this->factoryCreateUid(Group::class);
        $user->groups->push($group);
        $mapper->store($user);
        $this->seeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);
        $user = $mapper->find($userId);
        $this->assertCount(4, $user->groups);
    }

    /** @test */
    public function we_can_add_related_items_by_pushing_to_collection()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $group = new Group();
        $group->id = $this->randId();
        $group->name = 'test group';
        $user->groups->push($group);
        $mapper->store($user);
        $this->seeInDatabase('groups', [
            'id'   => $group->id,
            'name' => 'test group',
        ]);
        $this->seeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);
    }

    /** @test */
    public function we_can_unsync_many_to_many_relationship_by_setting_collection_to_null()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);

        $user->groups = null;
        $mapper->store($user);

        $this->dontSeeInDatabase('groups_users', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function we_can_unsync_many_to_many_relationship_by_removing_items_from_collection()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);

        foreach ($user->groups as $group) {
            $user->groups->pull($group->id);
        }
        $mapper->store($user);

        $this->dontSeeInDatabase('groups_users', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function we_can_unsync_many_to_many_relationship_by_reseting_collection()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);

        $user->groups = new Collection();
        $mapper->store($user);

        $this->dontSeeInDatabase('groups_users', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function we_can_remove_a_single_item_from_a_many_to_many_relationship_using_collection_pull()
    {
        $userId = $this->createRelatedSet(1);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $group = $user->groups->first();

        $user->groups->pull($group->id);
        $mapper->store($user);

        $this->dontSeeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);
    }

    /** @test */
    public function we_can_remove_a_single_item_from_a_many_to_many_relationship_using_collection_forget()
    {
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $group = $user->groups->first();

        $user->groups->forget($group->id);
        $mapper->store($user);

        $this->dontSeeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);
        $user = $mapper->find($userId);
        $this->assertCount(2, $user->groups);
    }

    /** @test */
    public function we_can_remove_a_single_item_from_a_many_to_many_relationship_using_collection_pop()
    {
        $userId = $this->createRelatedSet(1);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $group = $user->groups->first();

        $user->groups->pop();
        $mapper->store($user);

        $this->dontSeeInDatabase('groups_users', [
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);
    }

    /** @test */
    public function we_can_use_a_many_to_many_relationship_on_entities_with_custom_primary_keys()
    {
        $user = new CustomUser;
        $user->name = "Test User";
        $groupA = new CustomGroup;
        $groupA->name = "Test Group A";
        $groupB = new CustomGroup;
        $groupB->name = "Test Group B";
        $user->groups = [$groupA, $groupB];
        $mapper = $this->mapper(CustomUser::class);
        $mapper->store($user);
        
        $this->seeInDatabase('custom_users', [
            'name' => 'Test User',
        ]);
        $this->seeInDatabase('custom_groups', [
            'name' => 'Test Group A',
        ]);
        $this->seeInDatabase('custom_groups', [
            'name' => 'Test Group B',
        ]);

        // Test lazy loading
        $users = $mapper->get();
        $this->assertCount(1, $users);
        $this->assertCount(2, $users->first()->groups);

        // Test Inverse Lazy Loading
        $groups = $this->mapper(CustomGroup::class)->get();
        $this->assertCount(2, $groups);
        $this->assertCount(1, $groups->first()->users);
        setTddOn();
        // Test Inverse Eager Loading
        $groups = $this->mapper(CustomGroup::class)->with('users')->get();
        $this->assertCount(2, $groups);
        $this->assertCount(1, $groups->first()->users);
        setTddOff();
        // Test Eager loading
        $users = $mapper->with('groups')->get();
        $this->assertCount(1, $users);
        $this->assertCount(2, $users->first()->groups);
    }

    /**
     * Create a random related set.
     *
     * @return int
     */
    protected function createRelatedSet($relatedCount = 1)
    {
        $userId = $this->insertUser();
        for ($x = 1; $x <= $relatedCount; $x++) {
            $groupId = $this->rawInsert('groups', [
                'id'   => $this->randId(),
                'name' => $this->faker()->sentence,
            ]);
            $this->rawInsert('groups_users', [
                'user_id'  => $userId,
                'group_id' => $groupId,
            ]);
        }

        return $userId;
    }
}
