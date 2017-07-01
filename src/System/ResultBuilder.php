<?php

namespace Analogue\ORM\System;

use Analogue\ORM\Relationships\Relationship;
use Closure;

class ResultBuilder
{
    /**
     * The default mapper used to build entities with.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $defaultMapper;

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
     * @param Mapper $defaultMapper
     * @param array  $eagerLoads
     */
    public function __construct(Mapper $defaultMapper)
    {
        $this->defaultMapper = $defaultMapper;
        $this->entityMap = $defaultMapper->getEntityMap();
    }

    /**
     * Convert a result set into an array of entities.
     *
     * @param array $results
     * @param array $eagerLoads name of the relation to be eager loaded on the Entities
     *
     * @return \Illuminate\Support\Collection
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

        // Note : Maybe we could use a PolymorphicResultBuilder, which would
        // be shared by both STI and polymorphic relations, as they share the
        // same process.

        switch ($this->entityMap->getInheritanceType()) {
            case 'single_table':
                return $this->buildUsingSingleTableInheritance($results);
                break;

            default:
                return $this->buildWithDefaultMapper($results);
                break;
        }
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
        switch ($this->entityMap->getInheritanceType()) {
            case 'single_table':

                $this->cacheSingleTableInheritanceResults($results);
                break;

            default:
                $mapper = $this->defaultMapper;
                 // When hydrating EmbeddedValue object, they'll likely won't
                // have a primary key set.
                if (!is_null($mapper->getEntityMap()->getKeyName())) {
                    $mapper->getEntityCache()->add($results);
                }
                break;
        }
    }

    /**
     * Cache results from a STI result set.
     *
     * @param array $results
     *
     * @return void
     */
    protected function cacheSingleTableInheritanceResults(array $results)
    {
        foreach ($results as $result) {
            $mapper = $this->getMapperForSingleRow($result);

            // When hydrating EmbeddedValue object, they'll likely won't
            // have a primary key set.
            if (!is_null($mapper->getEntityMap()->getKeyName())) {
                $mapper->getEntityCache()->add([$result]);
            }
        }
    }

    /**
     * Build embedded objects and match them to the result set.
     *
     * @param array $results
     *
     * @return array
     */
    protected function buildEmbeddedRelationships(array $results) : array
    {
        $entityMap = $this->entityMap;
        $instance = $this->defaultMapper->newInstance();
        $embeddeds = $entityMap->getEmbeddedRelationships();

        foreach ($embeddeds as $embedded) {
            $results = $entityMap->$embedded($instance)->match($results, $embedded);
        }

        return $results;
    }

    /**
     * Launch queries on eager loaded relationships.
     *
     * @return array
     */
    protected function queryEagerLoadedRelationships(array $results, array $eagerLoads) : array
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
    protected function parseRelations(array $relations)
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
    protected function parseNested($name, $results)
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
    public function eagerLoadRelations(array $results)
    {
        foreach ($this->eagerLoads as $name => $constraints) {

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
    protected function loadRelation(array $results, $name, Closure $constraints) : array
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
    public function getRelation($relation)
    {
        // We want to run a relationship query without any constrains so that we will
        // not have to remove these where clauses manually which gets really hacky
        // and is error prone while we remove the developer's own where clauses.
        $query = Relationship::noConstraints(function () use ($relation) {
            return $this->entityMap->$relation($this->defaultMapper->newInstance());
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
    protected function nestedRelations($relation)
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
    protected function isNested($name, $relation)
    {
        $dots = str_contains($name, '.');

        return $dots && starts_with($name, $relation.'.');
    }

    /**
     * Build an entity from results, using the default mapper on this builder.
     * This is the default build plan when no table inheritance is being used.
     *
     * @param array $results
     *
     * @return Collection
     */
    protected function buildWithDefaultMapper(array $results)
    {
        $builder = new EntityBuilder($this->defaultMapper, array_keys($this->eagerLoads));

        return collect($results)->map(function ($item, $key) use ($builder) {
            return $builder->build($item);
        })->all();
    }

    /**
     * Build an entity from results, using single table inheritance.
     *
     * @param array $results
     *
     * @return Collection
     */
    protected function buildUsingSingleTableInheritance(array $results)
    {
        return collect($results)->map(function ($item, $key) {
            $builder = $this->builderForResult($item);

            return $builder->build($item);
        })->all();
    }

    /**
     * Given a result array, return the entity builder needed to correctly
     * build the result into an entity. If no getDiscriminatorColumnMap property
     * has been defined on the EntityMap, we'll assume that the value stored in
     * the $type column is the fully qualified class name of the entity and
     * we'll use it instead.
     *
     * @param array $result
     *
     * @return EntityBuilder
     */
    protected function builderForResult(array $result)
    {
        $type = $result[$this->entityMap->getDiscriminatorColumn()];

        $mapper = $this->getMapperForSingleRow($result);

        if (!isset($this->builders[$type])) {
            $this->builders[$type] = new EntityBuilder(
                $mapper,
                array_keys($this->eagerLoads)
            );
        }

        return $this->builders[$type];
    }

    /**
     * Get mapper corresponding to the result type.
     *
     * @param array $result
     *
     * @return Mapper
     */
    protected function getMapperForSingleRow(array $result) : Mapper
    {
        $type = $result[$this->entityMap->getDiscriminatorColumn()];

        $columnMap = $this->entityMap->getDiscriminatorColumnMap();

        $class = isset($columnMap[$type]) ? $columnMap[$type] : $type;

        return Manager::getInstance()->mapper($class);
    }
}
