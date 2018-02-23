<?php

use Analogue\ORM\System\Proxies\CollectionProxy;
use Illuminate\Support\Collection;
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
    }

    /** @test */
    public function we_can_remove_from_a_lazy_collection_without_loading_it()
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
