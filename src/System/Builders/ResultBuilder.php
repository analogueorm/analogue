<?php

namespace Analogue\ORM\System\Builders;

use Analogue\ORM\Relationships\Relationship;
use Analogue\ORM\System\Mapper;
use Closure;

class ResultBuilder implements ResultBuilderInterface
{
    /**
     * The mapper used to build entities with.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $mapper;

    /**
     * Relations that will be eager loaded on this query.
     *
     * @var array
     */
    protected $eagerLoads;

    /**
     * The Entity Map for the entity to build.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * An array of builders used by this class to build necessary
     * entities for each result type.
     *
     * @var array
     */
    protected $builders = [];

    /**
     * Whether to use result and entity caching.
     *
     * @var bool
     */
    protected $useCache;

    /**
     * ResultBuilder constructor.
     *
     * @param Mapper $mapper   The mapper used to build entities with.
     * @param bool   $useCache [optional] Whether to use result and entity caching. Defaults to false.
     */
    public function __construct(Mapper $mapper, bool $useCache = false)
    {
        $this->mapper = $mapper;
        $this->entityMap = $mapper->getEntityMap();
        $this->useCache = $useCache;
    }

    /**
     * Convert a result set into an array of entities.
     *
     * @param array $results    The results to convert into entities.
     * @param array $eagerLoads name of the relation(s) to be eager loaded on the Entities
     *
     * @return array
     */
    public function build(array $results, array $eagerLoads)
    {
        // First, we'll cache the raw result set
        $this->cacheResults($results);

        // Parse embedded relations and build corresponding entities using the default
        // mapper.
        $results = $this->buildEmbeddedRelationships($results);

        // Launch the queries related to eager loads, and match the
        // current result set to these loaded relationships.
        $results = $this->queryEagerLoadedRelationships($results, $eagerLoads);

        return $this->buildResultSet($results);
    }

    /**
     * Cache result set.
     *
     * @param array $results
     *
     * @return void
     */
    protected function cacheResults(array $results)
    {
        $mapper = $this->mapper;

        // When hydrating EmbeddedValue object, they'll likely won't
        // have a primary key set.
        if ($mapper->getEntityMap()->getKeyName() !== null) {
            $mapper->getEntityCache()->add($results);
        }
    }

    /**
     * Build embedded objects and match them to the result set.
     *
     * @param array $results
     *
     * @return array
     */
    protected function buildEmbeddedRelationships(array $results): array
    {
        $entityMap = $this->entityMap;
        $instance = $this->mapper->newInstance();
        $embeddeds = $entityMap->getEmbeddedRelationships();

        foreach ($embeddeds as $embedded) {
            $results = $entityMap->$embedded($instance)->match($results, $embedded);
        }

        return $results;
    }

    /**
     * Launch queries on eager loaded relationships.
     *
     * @param array $results
     * @param array $eagerLoads
     *
     * @return array
     */
    protected function queryEagerLoadedRelationships(array $results, array $eagerLoads): array
    {
        $this->eagerLoads = $this->parseRelations($eagerLoads);

        return $this->eagerLoadRelations($results);
    }

    /**
     * Parse a list of relations into individuals.
     *
     * @param array $relations
     *
     * @return array
     */
    protected function parseRelations(array $relations): array
    {
        $results = [];

        foreach ($relations as $name => $constraints) {
            // If the "relation" value is actually a numeric key, we can assume that no
            // constraints have been specified for the eager load and we'll just put
            // an empty Closure with the loader so that we can treat all the same.
            if (is_numeric($name)) {
                $f = function () {
                };

                list($name, $constraints) = [$constraints, $f];
            }

            // We need to separate out any nested includes. Which allows the developers
            // to load deep relationships using "dots" without stating each level of
            // the relationship with its own key in the array of eager load names.
            $results = $this->parseNested($name, $results);

            $results[$name] = $constraints;
        }

        return $results;
    }

    /**
     * Parse the nested relationships in a relation.
     *
     * @param string $name
     * @param array  $results
     *
     * @return array
     */
    protected function parseNested(string $name, array $results): array
    {
        $progress = [];

        // If the relation has already been set on the result array, we will not set it
        // again, since that would override any constraints that were already placed
        // on the relationships. We will only set the ones that are not specified.
        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;

            if (!isset($results[$last = implode('.', $progress)])) {
                $results[$last] = function () {
                };
            }
        }

        return $results;
    }

    /**
     * Eager load the relationships on a result set.
     *
     * @param array $results
     *
     * @return array
     */
    public function eagerLoadRelations(array $results): array
    {
        foreach ($this->eagerLoads as $name => $constraints) {
            // First, we'll check if the entity map has a relation and just pass if it
            // is not the case

            if (!in_array($name, $this->entityMap->getRelationships())) {
                continue;
            }

            // For nested eager loads we'll skip loading them here and they will be set as an
            // eager load on the query to retrieve the relation so that they will be eager
            // loaded on that query, because that is where they get hydrated as models.
            if (strpos($name, '.') === false) {
                $results = $this->loadRelation($results, $name, $constraints);
            }
        }

        return $results;
    }

    /**
     * Eagerly load the relationship on a set of entities.
     *
     * @param array    $results
     * @param string   $name
     * @param \Closure $constraints
     *
     * @return array
     */
    protected function loadRelation(array $results, string $name, Closure $constraints): array
    {
        // First we will "back up" the existing where conditions on the query so we can
        // add our eager constraints. Then we will merge the wheres that were on the
        // query back to it in order that any where conditions might be specified.
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($results);

        call_user_func($constraints, $relation);

        // Once we have the results, we just match those back up to their parent models
        // using the relationship instance. Then we just return the finished arrays
        // of models which have been eagerly hydrated and are readied for return.
        return $relation->match($results, $name);
    }

    /**
     * Get the relation instance for the given relation name.
     *
     * @param string $relation
     *
     * @return \Analogue\ORM\Relationships\Relationship
     */
    public function getRelation(string $relation): Relationship
    {
        // We want to run a relationship query without any constrains so that we will
        // not have to remove these where clauses manually which gets really hacky
        // and is error prone while we remove the developer's own where clauses.
        $query = Relationship::noConstraints(function () use ($relation) {
            return $this->entityMap->$relation($this->mapper->newInstance());
        });

        $nested = $this->nestedRelations($relation);

        // If there are nested relationships set on the query, we will put those onto
        // the query instances so that they can be handled after this relationship
        // is loaded. In this way they will all trickle down as they are loaded.
        if (count($nested) > 0) {
            $query->getQuery()->with($nested);
        }

        return $query;
    }

    /**
     * Get the deeply nested relations for a given top-level relation.
     *
     * @param string $relation
     *
     * @return array
     */
    protected function nestedRelations(string $relation): array
    {
        $nested = [];

        // We are basically looking for any relationships that are nested deeper than
        // the given top-level relationship. We will just check for any relations
        // that start with the given top relations and adds them to our arrays.
        foreach ($this->eagerLoads as $name => $constraints) {
            if ($this->isNested($name, $relation)) {
                $nested[substr($name, strlen($relation.'.'))] = $constraints;
            }
        }

        return $nested;
    }

    /**
     * Determine if the relationship is nested.
     *
     * @param string $name
     * @param string $relation
     *
     * @return bool
     */
    protected function isNested(string $name, string $relation): bool
    {
        $dots = str_contains($name, '.');

        return $dots && starts_with($name, $relation.'.');
    }

    /**
     * Build an entity from results.
     *
     * @param array $results
     *
     * @return array
     */
    protected function buildResultSet(array $results): array
    {
        return $this->buildUnkeyedResultSet($results);
    }

    /**
     * Build a result set.
     *
     * @param array $results
     *
     * @return array
     */
    protected function buildUnkeyedResultSet(array $results): array
    {
        $builder = new EntityBuilder($this->mapper, array_keys($this->eagerLoads), $this->useCache);

        return array_map(function ($item) use ($builder) {
            return $builder->build($item);
        }, $results);
    }

    /**
     * Build a result set keyed by PK.
     *
     * @param array   $results
     * @param string. $primaryKey
     *
     * @return array
     */
    protected function buildKeyedResultSet(array $results, string $primaryKey): array
    {
        $builder = new EntityBuilder($this->mapper, array_keys($this->eagerLoads), $this->useCache);

        $keys = array_map(function ($item) use ($primaryKey) {
            return $item[$primaryKey];
        }, $results);

        return array_combine($keys, array_map(function ($item) use ($builder) {
            return $builder->build($item);
        }, $results));
    }
}
