<?php

namespace Analogue\ORM\System\Proxies;

use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\System\Manager;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * This class aims to generate Entity Proxies using
 * ProxyManager, and based on a parent Entity and its
 * relationship method.
 */
class ProxyFactory
{
    public function make($entity, $relation, $class)
    {
        $entityMap = Manager::getMapper($entity)->getEntityMap();

        $singleRelations = $entityMap->getSingleRelationships();
        $manyRelations = $entityMap->getManyRelationships();

        if (in_array($relation, $singleRelations)) {
            return $this->makeEntityProxy($entity, $relation, $class);
        }

        if (in_array($relation, $manyRelations)) {
            return new CollectionProxy($entity, $relation);
        }

        throw new MappingException("Could not identify relation '$relation'");
    }

    /**
     * Create an instance of a proxy object, extending the actual
     * related class.
     *
     * @param mixed  $entity   parent object
     * @param string $relation the name of the relationship method
     * @param string $class    the class name of the related object
     *
     * @return mixed
     */
    protected function makeEntityProxy($entity, $relation, $class)
    {
        $proxyPath = Manager::getInstance()->getProxyPath();

        if ($proxyPath !== null) {
            $proxyConfig = new Configuration();
            $proxyConfig->setProxiesTargetDir($proxyPath);

            $factory = new LazyLoadingValueHolderFactory($proxyConfig);
        } else {
            $factory = new LazyLoadingValueHolderFactory();
        }

        $initializer = function (&$wrappedObject, LazyLoadingInterface $proxy, $method, array $parameters, &$initializer) use ($entity, $relation) {
            $entityMap = Manager::getMapper($entity)->getEntityMap();

            $wrappedObject = $entityMap->$relation($entity)->getResults($relation);

            $initializer = null; // disable initialization
            return true; // confirm that initialization occurred correctly
        };

        return $factory->createProxy($class, $initializer);
    }
}
