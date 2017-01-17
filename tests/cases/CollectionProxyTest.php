<?php 

use TestApp\Blog;

use Illuminate\Support\Collection;
use Analogue\ORM\System\Proxies\CollectionProxy;

class CollectionProxyTest extends DomainTestCase
{

    /** @test */
    public function all_collection_methods_are_overloaded()
    {
        // Ignoring methods that are mostly alias, or shortcuts
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
        ];

        $collectionClass = new ReflectionClass(Collection::class);        
        $collectionMethods = array_map(function($method) {
            return $method->isPublic() ? $method->name : null;
        },$collectionClass->getMethods());

        $proxyClass = new ReflectionClass(CollectionProxy::class);
        $proxyMethods = array_map(function($method) {
            return $method->class == CollectionProxy::class ? $method->name : null;
        },$proxyClass->getMethods());

        foreach($collectionMethods as $parentMethod) {
            if (in_array($parentMethod, $ignoredMethods)) continue;
            
            if(! in_array($parentMethod, $proxyMethods)) {
                throw new \Exception("$parentMethod should be ovverided");
            }
        }

        $this->assertTrue(true);

    }

    /** @test */
    public function we_can_push_to_a_collection_proxy_without_loading_it()
    {

    }

    /** @test */
    public function we_can_remove_from_a_lazy_collection_without_loading_it()
    {
        
    }
}