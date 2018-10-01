<?php

use Analogue\ORM\EntityCollection;
use Illuminate\Support\Collection;
use ProxyManager\Proxy\ProxyInterface;
use TestApp\CustomGroup;
use TestApp\CustomUser;
use TestApp\Group;
use TestApp\User;

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
    public function we_can_retrieve_a_one_belongs_to_many_relationship()
    {
        // One user belonging to many groups
        $userId = $this->createRelatedSet(3);
        $mapper = $this->mapper(User::class);
        $user = $mapper->find($userId);
        $this->assertCount(3, $user->groups);
        foreach ($user->groups as $group) {
            $this->assertInstanceOf(Group::class, $group);
        }
    }

    /** @test */
    public function we_can_retrieve_a_many_belongs_to_many_relationship()
    {
        // Many users belonging to many groups
        $userIdsToGroupIds = $this->createRelatedSets(3, 3);

        // Test that the relationships we've set up load appropriately
        $mapper = $this->mapper(User::class);
        foreach ($userIdsToGroupIds as $userId => $groupIds) {
            $user = $mapper->find($userId);
            $this->assertCount(count($groupIds), $user->groups);
            foreach ($user->groups as $group) {
                $this->assertContains($group->id, $groupIds);
                $this->assertInstanceOf(Group::class, $group);
            }
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
    public function related_items_are_not_created_twice_when_storing_twice()
    {
        $database = $this->app->make('db');
        $userId = $this->createRelatedSet(1);
        $this->assertEquals(1, $database->table('groups_users')->count());
        $mapper = $this->mapper(User::class);
        $user = $mapper->with('groups')->whereId($userId)->first();
        $mapper->store($user);
        $this->assertEquals(1, $database->table('groups_users')->count());
    }

    /** @test */
    public function one_belongs_to_many_relationship_can_be_eager_loaded()
    {
        $database = $this->app->make('db');
        $userId = $this->createRelatedSet(1);
        $this->assertEquals(1, $database->table('groups_users')->count());
        $mapper = $this->mapper(User::class);
        $user = $mapper->with('groups')->whereId($userId)->first();
        $this->assertCount(1, $user->groups);
        $this->assertInstanceOf(EntityCollection::class, $user->groups);
        $this->assertNotInstanceOf(ProxyInterface::class, $user->groups);
    }

    /** @test */
    public function many_belongs_to_many_relationship_can_be_eager_loaded()
    {
        // Create many users belonging to many groups
        $userIdsToGroupIds = $this->createRelatedSets(2, 2);

        // Test that the relationships we've set up are eager loaded appropriately
        $mapper = $this->mapper(User::class);

        $users = $mapper->with('groups')->get();

        foreach ($users as $user) {
            $groupIds = $userIdsToGroupIds[$user->id];
            $this->assertNotInstanceOf(ProxyInterface::class, $user->groups);
            $this->assertInstanceOf(EntityCollection::class, $user->groups);
            $this->assertCount(count($groupIds), $user->groups);
            foreach ($user->groups as $group) {
                $this->assertContains($group->id, $groupIds);
                $this->assertInstanceOf(Group::class, $group);
            }
        }
    }

    /** @test */
    public function empty_eagerloaded_belongs_to_many_relationship_is_an_empty_entity_collection()
    {
        $userId = $this->insertUser();
        $mapper = $this->mapper(User::class);
        $user = $mapper->with('groups')->whereId($userId)->first();
        $this->assertInstanceOf(EntityCollection::class, $user->groups);
        $this->assertNotInstanceOf(ProxyInterface::class, $user->groups);
        $this->assertCount(0, $user->groups);
    }

    /** @test */
    public function we_can_use_a_many_to_many_relationship_on_entities_with_custom_primary_keys()
    {
        $user = new CustomUser();
        $user->name = 'Test User';
        $groupA = new CustomGroup();
        $groupA->name = 'Test Group A';
        $groupB = new CustomGroup();
        $groupB->name = 'Test Group B';
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

        // Test Inverse Eager Loading
        $groups = $this->mapper(CustomGroup::class)->with('users')->get();
        $this->assertCount(2, $groups);
        $this->assertCount(1, $groups->first()->users);

        // Test Eager loading
        $users = $mapper->with('groups')->get();
        $this->assertCount(1, $users);
        $this->assertCount(2, $users->first()->groups);
    }

    /**
     * Create a random related set.
     *
     * @param int $relatedCount
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

    /**
     * Create two randomly related sets.
     *
     * @param int $rootCount    [optional] The number of root entities to create. Defaults to 3.
     * @param int $relatedCount [optional] The number of related entities to create. Defaults to 3.
     *
     * @return array [userId => [groupId, groupId, ...], ...] A map of root entity IDs as keys to a list of related entity IDs as values
     */
    protected function createRelatedSets($rootCount = 3, $relatedCount = 3)
    {
        $userIds = [];
        $groupIds = [];
        $userIdsToGroupIds = [];

        // Create users
        for ($i = 0; $i < $rootCount; $i++) {
            $userIds[] = $this->insertUser();
        }

        // Create groups
        for ($i = 0; $i < $relatedCount; $i++) {
            $groupIds[] = $this->insertGroup();
        }

        // Relate each user to every group
        for ($i = 0; $i < $rootCount; $i++) {
            $userId = $userIds[$i];

            for ($j = 0; $j < $relatedCount; $j++) {
                $groupId = $groupIds[$j];

                $this->rawInsert('groups_users', [
                    'user_id'  => $userId,
                    'group_id' => $groupId,
                ]);

                if (empty($userIdsToGroupIds[$userId])) {
                    $userIdsToGroupIds[$userId] = [];
                }

                $userIdsToGroupIds[$userId][] = $groupId;
            }
        }

        return $userIdsToGroupIds;
    }
}
