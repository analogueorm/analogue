<?php

namespace Analogue\ORM\System\Cache;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\EntityMap;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\Mappable;
use Analogue\ORM\Relationships\Relationship;
use Analogue\ORM\System\Aggregate;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Wrappers\Factory;

/**
 * Attribute cache is tracking raw attributes between entity queries.
 */
class AttributeCache
{
    /**
     * Entity's raw attributes/relationships.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Primary key => Entity instance correspondancy.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Entity Map for the current Entity Type.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * Wrapper factory.
     *
     * @var \Analogue\ORM\System\Wrappers\Factory
     */
    protected $factory;

    /**
     * Associative array containing list of pivot attributes per relationship
     * so we don't have to call relationship method on refresh.
     *
     * @var array
     */
    protected $pivotAttributes = [];

    /**
     * EntityCache constructor.
     *
     * @param EntityMap $entityMap
     */
    public function __construct(EntityMap $entityMap)
    {
        $this->entityMap = $entityMap;

        $this->factory = new Factory();
    }

    /**
     * Add an array of key=>attributes representing
     * the initial state of loaded entities.
     *
     * @param array $results
     */
    public function add(array $results)
    {
        $cachedResults = [];

        $keyColumn = $this->entityMap->getKeyName();

        foreach ($results as $result) {
            $id = $result[$keyColumn];

            // Forget the ID field from the cache attributes
            // to prevent any side effect.
            // TODO : remove primary key check from dirty attributes parsing
            //unset($result[$keyColumn]);
            $cachedResults[$id] = $this->rawResult($result);
        }

        if (empty($this->cache)) {
            $this->cache = $cachedResults;
        } else {
            $this->mergeCacheResults($cachedResults);
        }
    }

    /**
     * Return result without any collection or object.
     *
     * @param array $result
     *
     * @return array
     */
    protected function rawResult(array $result): array
    {
        return $result;

        return array_filter($result, function ($attribute) {
            return !is_object($attribute);
        });
    }

    /**
     * Retrieve initial attributes for a single entity.
     *
     * @param string $id
     *
     * @return array
     */
    public function get(string $id = null): array
    {
        if ($this->has($id)) {
            return $this->cache[$id];
        }

        return [];
    }

    /**
     * Check if a record for this id exists.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id = null): bool
    {
        return array_key_exists($id, $this->cache);
    }

    /**
     * Combine new result set with existing attributes in
     * cache.
     *
     * @param array $results
     *
     * @return void
     */
    protected function mergeCacheResults(array $results)
    {
        foreach ($results as $key => $entity) {
            $this->cache[$key] = $entity;
        }
    }

    /**
     * Cache Relation's query result for an entity.
     *
     * @param string       $key          primary key of the cached entity
     * @param string       $relation     name of the relation
     * @param mixed        $results      results of the relationship's query
     * @param Relationship $relationship
     *
     * @throws MappingException
     *
     * @return void
     */
    public function cacheLoadedRelationResult(string $key, string $relation, $results, Relationship $relationship)
    {
        if ($results instanceof EntityCollection) {
            $this->cacheManyRelationResults($key, $relation, $results, $relationship);
        }

        // TODO : As we support popo Entities, Maybe this check isn't needed anymore,
        // or we have to check that $result is an object instead
        if ($results instanceof Mappable) {
            $this->cacheSingleRelationResult($key, $relation, $results, $relationship);
        }
    }

    /**
     * Create a cachedRelationship instance which will hold related entity's hash and pivot attributes, if any.
     *
     * @param string       $relation
     * @param array        $result
     * @param Relationship $relationship
     *
     * @throws MappingException
     *
     * @return CachedRelationship
     */
    protected function getCachedRelationship(string $relation, $result, Relationship $relationship)
    {
        $pivotColumns = $relationship->getPivotAttributes();

        if (!array_key_exists($relation, $this->pivotAttributes)) {
            $this->pivotAttributes[$relation] = $pivotColumns;
        }

        $wrapper = $this->factory->make($result);

        $hash = $wrapper->getEntityHash();

        if (count($pivotColumns) > 0) {
            $pivotAttributes = [];
            foreach ($pivotColumns as $column) {
                $pivot = $wrapper->getEntityAttribute('pivot');

                $pivotWrapper = $this->factory->make($pivot);

                $pivotAttributes[$column] = $pivotWrapper->getEntityAttribute($column);
            }

            $cachedRelationship = new CachedRelationship($hash, $pivotAttributes);
        } else {
            $cachedRelationship = new CachedRelationship($hash);
        }

        return $cachedRelationship;
    }

    /**
     * Cache a many relationship.
     *
     * @param string           $parentKey
     * @param string           $relation
     * @param EntityCollection $results
     * @param Relationship     $relationship
     *
     * @throws MappingException
     */
    protected function cacheManyRelationResults(string $parentKey, string $relation, $results, Relationship $relationship)
    {
        $this->cache[$parentKey][$relation] = [];

        foreach ($results as $result) {
            $cachedRelationship = $this->getCachedRelationship($relation, $result, $relationship);

            $relatedHash = $cachedRelationship->getHash();

            $this->cache[$parentKey][$relation][$relatedHash] = $cachedRelationship;
        }
    }

    /**
     * Cache a single relationship.
     *
     * @param string       $parentKey
     * @param string       $relation
     * @param Mappable     $result
     * @param Relationship $relationship
     *
     * @throws MappingException
     */
    protected function cacheSingleRelationResult(string $parentKey, string $relation, $result, Relationship $relationship)
    {
        $this->cache[$parentKey][$relation] = $this->getCachedRelationship($relation, $result, $relationship);
    }

    /**
     * Get Entity's Hash.
     *
     * @param  $entity
     *
     * @throws MappingException
     *
     * @return string
     */
    protected function getEntityHash(InternallyMappable $entity): string
    {
        $class = $entity->getEntityClass();

        $mapper = Manager::getMapper($class);

        $keyName = $mapper->getEntityMap()->getKeyName();

        return $class.'.'.$entity->getEntityAttribute($keyName);
    }

    /**
     * Refresh the cache record for an aggregated entity after a write operation.
     *
     * @param Aggregate $entity
     */
    public function refresh(Aggregate $entity)
    {
        $this->cache[$entity->getEntityKeyValue()] = $this->transform($entity);
    }

    /**
     * Transform an Aggregated Entity into a cache record.
     *
     * @param Aggregate $aggregatedEntity
     *
     * @throws MappingException
     *
     * @return array
     */
    protected function transform(Aggregate $aggregatedEntity): array
    {
        $baseAttributes = $aggregatedEntity->getRawAttributes();

        $relationAttributes = [];

        // First we'll handle each relationships that are a one to one
        // relation, and which will be saved as a CachedRelationship
        // object inside the cache.

        // NOTE : storing localRelationships maybe useless has we store
        // the foreign key in the attributes already.

        foreach ($this->entityMap->getSingleRelationships() as $relation) {
            $aggregates = $aggregatedEntity->getRelationship($relation);

            if (count($aggregates) == 1) {
                $related = $aggregates[0];
                $relationAttributes[$relation] = new CachedRelationship($related->getEntityHash());
            }
            if (count($aggregates) > 1) {
                throw new MappingException("Single Relationship '$relation' contains several related entities");
            }
        }

        // Then we'll handle the 'many' relationships and store them as
        // an array of CachedRelationship objects.

        foreach ($this->entityMap->getManyRelationships() as $relation) {
            $aggregates = $aggregatedEntity->getRelationship($relation);

            $relationAttributes[$relation] = [];

            foreach ($aggregates as $aggregate) {
                $relationAttributes[$relation][] = new CachedRelationship(
                    $aggregate->getEntityHash(),
                    $aggregate->getPivotAttributes()
                );
            }
        }

        return $baseAttributes + $relationAttributes;
    }

    /**
     * Get pivot attributes for a relation.
     *
     * @param string             $relation
     * @param InternallyMappable $entity
     *
     * @return array
     */
    protected function getPivotValues(string $relation, InternallyMappable $entity): array
    {
        $values = [];

        $entityAttributes = $entity->getEntityAttributes();

        if (array_key_exists($relation, $this->pivotAttributes)) {
            foreach ($this->pivotAttributes[$relation] as $attribute) {
                if (array_key_exists($attribute, $entityAttributes)) {
                    $values[$attribute] = $entity->getEntityAttribute('pivot')->$attribute;
                }
            }
        }

        return $values;
    }

    /**
     * Clear the entity Cache. Use with caution as it could result
     * in unpredictable behaviour if the cached entities are stored
     * after the cache clear operation.
     *
     * @return void
     */
    public function clear()
    {
        $this->cache = [];
        $this->pivotAttributes = [];
    }
}
