<?php

use Analogue\ORM\System\Proxies\CollectionProxy;
use Illuminate\Support\Collection;
use TestApp\Group;
use TestApp\User;

class CollectionProxyTest extends DomainTestCase
{
    /** @test */
    public function all_collection_methods_are_overloaded()
    {
        // Ignoring methods that are mostly static, alias, or shortcuts
        $ignoredMethods = [
            'average',
            'isNotEmpty',
            'sortByDesc',
            'uniqueStrict',
            '__toString',
            'macro',
            'hasMacro',
            '__callStatic',
            'make',
            'proxy',
            'whereNotInStrict',
            'eachSpread',
            'mapSpread',
            'mapToGroups',
            'concat',
            'unless',
            'wrap',
            'unwrap',
            'mixin',
            'dd',
            'dump',
            'sortKeysDesc',
        ];

        $collectionClass = new ReflectionClass(Collection::class);
        $collectionMethods = array_map(function ($method) {
            return $method->isPublic() ? $method->name : null;
        }, $collectionClass->getMethods());

        $proxyClass = new ReflectionClass(CollectionProxy::class);
        $proxyMethods = array_map(function ($method) {
            return $method->class == CollectionProxy::class ? $method->name : null;
        }, $proxyClass->getMethods());

        foreach ($collectionMethods as $parentMethod) {
            if (in_array($parentMethod, $ignoredMethods)) {
                continue;
            }

            if (!in_array($parentMethod, $proxyMethods)) {
                throw new \Exception("$parentMethod should be ovverided");
            }
        }

        $this->assertTrue(true);
    }

    /** @test */
    public function we_can_do_a_count_operation_on_proxy_without_loading_it()
    {
        $id = $this->createRelatedSet(3);
        $user = mapper(User::class)->find($id);
        $this->assertFalse($user->groups->isProxyInitialized());
        $this->assertEquals(3, $user->groups->count());
        $this->assertFalse($user->groups->isProxyInitialized());
        $this->assertCount(3, $user->groups->all());
        $this->assertTrue($user->groups->isProxyInitialized());
    }

    /** @test */
    public function we_can_push_to_a_collection_proxy_without_loading_it()
    {
        $id = $this->createRelatedSet(0);
        $user = mapper(User::class)->find($id);
        $this->assertFalse($user->groups->isProxyInitialized());
        $group = new Group();
        $group->id = 666;
        $group->name = 'added-test';
        $user->groups->push($group);
        $this->assertFalse($user->groups->isProxyInitialized());
        $this->assertCount(1, $user->groups->getAddedItems());
        mapper(User::class)->store($user);
        $this->assertDatabaseHas('groups', [
            'name' => 'added-test',
        ]);
        $this->assertDatabaseHas('groups_users', [
            'group_id' => 666,
            'user_id'  => $id,
        ]);
        $this->clearCache();
        $user = mapper(User::class)->find($user->id);
        $this->assertEquals(666, $user->groups->first()->id);
    }

    /** @test */
    public function pushed_items_are_stored_if_relationship_is_loaded_after_push()
    {
        $id = $this->createRelatedSet(0);
        $user = mapper(User::class)->find($id);
        $this->assertFalse($user->groups->isProxyInitialized());
        $group = new Group();
        $group->id = 666;
        $group->name = 'added-test';
        $user->groups->push($group);
        $this->assertFalse($user->groups->isProxyInitialized());
        $this->assertCount(1, $user->groups->getAddedItems());
        $user->groups->initializeProxy();
        $this->assertTrue($user->groups->isProxyInitialized());

        mapper(User::class)->store($user);
        $this->assertDatabaseHas('groups', [
            'name' => 'added-test',
        ]);
        $this->assertDatabaseHas('groups_users', [
            'group_id' => 666,
            'user_id'  => $id,
        ]);
        $this->clearCache();
        $user = mapper(User::class)->find($user->id);
        $this->assertEquals(666, $user->groups->first()->id);
    }

    /** @test */
    public function pushed_items_are_stored_if_relationship_is_loaded_before_push()
    {
        $id = $this->createRelatedSet(0);
        $user = mapper(User::class)->find($id);
        $this->assertFalse($user->groups->isProxyInitialized());
        $user->groups->initializeProxy();
        $this->assertTrue($user->groups->isProxyInitialized());
        $group = new Group();
        $group->id = 666;
        $group->name = 'added-test';
        $user->groups->push($group);
        $this->assertCount(0, $user->groups->getAddedItems());
        mapper(User::class)->store($user);
        $this->assertDatabaseHas('groups', [
            'name' => 'added-test',
        ]);
        $this->assertDatabaseHas('groups_users', [
            'group_id' => 666,
            'user_id'  => $id,
        ]);
        $this->clearCache();
        $user = mapper(User::class)->find($user->id);
        $this->assertEquals(666, $user->groups->first()->id);
    }

    /** @test */
    public function we_can_prepend_to_a_collection_proxy_without_loading_it()
    {
        $id = $this->createRelatedSet(3);
        $user = mapper(User::class)->find($id);
        $this->assertFalse($user->groups->isProxyInitialized());
        $group = new Group();
        $group->id = 666;
        $group->name = 'added-test';
        $user->groups->prepend($group);
        $this->assertFalse($user->groups->isProxyInitialized());
        $this->assertCount(1, $user->groups->getAddedItems());
        mapper(User::class)->store($user);
        $this->assertDatabaseHas('groups', [
            'name' => 'added-test',
        ]);
        $this->assertDatabaseHas('groups_users', [
            'group_id' => 666,
            'user_id'  => $id,
        ]);
    }

    /** @test */
    public function we_can_remove_from_a_collection_proxy_without_loading_it()
    {
    }

    /**
     * Create a random related set.
     *
     * @return int
     */
    protected function createRelatedSet($relatedCount = 1)
    {
        $userId = $this->insertUser();
        for ($x = 0; $x <= $relatedCount - 1; $x++) {
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
