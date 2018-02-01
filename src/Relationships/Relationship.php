<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Query;
use Analogue\ORM\System\Wrappers\Factory;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Query\Expression;

/**
 * Class Relationship.
 *
 * @mixin Query
 */
abstract class Relationship
{
    /**
     * The mapper instance for the related entity.
     *
     * @var Mapper
     */
    protected $relatedMapper;

    /**
     * The Analogue Query Builder instance.
     *
     * @var Query
     */
    protected $query;

    /**
     * The parent entity proxy instance.
     *
     * @var InternallyMappable
     */
    protected $parent;

    /**
     * The parent entity map.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $parentMap;

    /**
     * The Parent Mapper instance.
     *
     * @var Mapper
     */
    protected $parentMapper;

    /**
     * The related entity instance.
     *
     * @var object
     */
    protected $related;

    /**
     * The related entity Map.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $relatedMap;

    /**
     * Indicate if the relationships use a pivot table.*.
     *
     * @var bool
     */
    protected static $hasPivot = false;

    /**
     * Indicates if the relation is adding constraints.
     *
     * @var bool
     */
    protected static $constraints = true;

    /**
     * Wrapper factory.
     *
     * @var \Analogue\ORM\System\Wrappers\Factory
     */
    protected $factory;

    /**
     * Create a new relation instance.
     *
     * @param Mapper $mapper
     * @param mixed  $parent
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     */
    public function __construct(Mapper $mapper, $parent)
    {
        $this->relatedMapper = $mapper;

        $this->query = $mapper->getQuery();

        $this->factory = new Factory();

        $this->parent = $this->factory->make($parent);

        $this->parentMapper = $mapper->getManager()->getMapper($parent);

        $this->parentMap = $this->parentMapper->getEntityMap();

        $this->related = $mapper->newInstance();

        $this->relatedMap = $mapper->getEntityMap();

        $this->addConstraints();
    }

    /**
     * Indicate if the relationship uses a pivot table.
     *
     * @return bool
     */
    public function hasPivot()
    {
        return static::$hasPivot;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    abstract public function addConstraints();

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $results
     *
     * @return void
     */
    abstract public function addEagerConstraints(array $results);

    /**
     * Match the eagerly loaded results to their parents, then return
     * updated results.
     *
     * @param array  $results
     * @param string $relation
     *
     * @return array
     */
    abstract public function match(array $results, $relation);

    /**
     * Get the results of the relationship.
     *
     * @param string $relation relation name in parent's entity map
     *
     * @return mixed
     */
    abstract public function getResults($relation);

    /**
     * Get the relationship for eager loading.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEager()
    {
        return $this->get();
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * @param Query $query
     * @param Query $parent
     *
     * @return Query
     */
    public function getRelationCountQuery(Query $query, Query $parent)
    {
        $query->select(new Expression('count(*)'));

        $key = $this->wrap($this->getQualifiedParentKeyName());

        return $query->where($this->getHasCompareKey(), '=', new Expression($key));
    }

    /**
     * Run a callback with constraints disabled on the relation.
     *
     * @param Closure $callback
     *
     * @return mixed
     */
    public static function noConstraints(Closure $callback)
    {
        static::$constraints = false;

        // When resetting the relation where clause, we want to shift the first element
        // off of the bindings, leaving only the constraints that the developers put
        // as "extra" on the relationships, and not original relation constraints.
        $results = call_user_func($callback);

        static::$constraints = true;

        return $results;
    }

    /**
     * Get all of the primary keys for an array of entities.
     *
     * @param array  $entities
     * @param string $key
     *
     * @return array
     */
    protected function getKeys(array $entities, $key = null)
    {
        if (is_null($key)) {
            $key = $this->relatedMap->getKeyName();
        }

        $host = $this;

        return array_unique(array_values(array_map(function ($value) use ($key, $host) {
            if (!$value instanceof InternallyMappable) {
                $value = $host->factory->make($value);
            }

            return $value->getEntityAttribute($key);
        }, $entities)));
    }

    /**
     * Get all the keys from a result set.
     *
     * @param array  $results
     * @param string $key
     *
     * @return array
     */
    protected function getKeysFromResults(array $results, $key = null)
    {
        if (is_null($key)) {
            $key = $this->parentMap->getKeyName();
        }

        return array_unique(array_values(array_map(function ($value) use ($key) {
            return $value[$key];
        }, $results)));
    }

    /**
     * Get the underlying query for the relation.
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the base query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getBaseQuery()
    {
        return $this->query->getQuery();
    }

    /**
     * Get the parent model of the relation.
     *
     * @return InternallyMappable
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent model of the relation.
     *
     * @param InternallyMappable $parent
     *
     * @return void
     */
    public function setParent(InternallyMappable $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    protected function getQualifiedParentKeyName()
    {
        return $this->parent->getQualifiedKeyName();
    }

    /**
     * Get the related entity of the relation.
     *
     * @return \Analogue\ORM\Entity
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * Get the related mapper for the relation.
     *
     * @return Mapper
     */
    public function getRelatedMapper()
    {
        return $this->relatedMapper;
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function createdAt()
    {
        return $this->parentMap->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function updatedAt()
    {
        return $this->parentMap->getUpdatedAtColumn();
    }

    /**
     * Get the name of the related model's "updated at" column.
     *
     * @return string
     */
    public function relatedUpdatedAt()
    {
        return $this->related->getUpdatedAtColumn();
    }

    /**
     * Wrap the given value with the parent query's grammar.
     *
     * @param string $value
     *
     * @return string
     */
    public function wrap($value)
    {
        return $this->parentMapper->getQuery()->getQuery()->getGrammar()->wrap($value);
    }

    /**
     * Get a fresh timestamp.
     *
     * @return Carbon
     */
    protected function freshTimestamp()
    {
        return new Carbon();
    }

    /**
     * Cache the link between parent and related
     * into the mapper's Entity Cache.
     *
     * @param EntityCollection|Mappable $results  result of the relation query
     * @param string                    $relation name of the relation method on the parent entity
     *
     * @return void
     */
    protected function cacheRelation($results, $relation)
    {
        $cache = $this->parentMapper->getEntityCache();

        $cache->cacheLoadedRelationResult($this->parent->getEntityKeyName(), $relation, $results, $this);
    }

    /**
     * Return Pivot attributes when available on a relationship.
     *
     * @return array
     */
    public function getPivotAttributes()
    {
        return [];
    }

    /**
     * Get a combo type.primaryKey.
     *
     * @param Mappable $entity
     *
     * @return string
     */
    protected function getEntityHash(Mappable $entity)
    {
        $class = get_class($entity);

        $keyName = Mapper::getMapper($class)->getEntityMap()->getKeyName();

        return $class.'.'.$entity->getEntityAttribute($keyName);
    }

    /**
     * Run synchronization content if needed by the
     * relation type.
     *
     * @param array $actualContent
     *
     * @return void
     */
    abstract public function sync(array $actualContent);

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $result = call_user_func_array([$this->query, $method], $parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
