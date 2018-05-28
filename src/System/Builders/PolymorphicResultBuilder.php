<?php

namespace Analogue\ORM\System\Builders;

use Closure;
use Analogue\ORM\Relationships\Relationship;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Manager;

class PolymorphicResultBuilder implements ResultBuilderInterface
{
    /**
     * The default mapper used to build entities with.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $defaultMapper;

    /**
     * Reference to all mappers used in this result set
     * 
     * @var array
     */
    protected $mappers = [];

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
     * ResultBuilder constructor.
     *
     * @param Mapper $defaultMapper
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
     * @param array $eagerLoads name of the relation(s) to be eager loaded on the Entities
     *
     * @return array
     */
    public function build(array $results, array $eagerLoads)
    {
        // Make a list of all primary key of the current result set. This will
        // allow us to group all polymorphic operations by type, then put
        // back every object in the intended order. 
        $primaryKeyColumn = $this->entityMap->getKeyName();
        $ids = array_map(function($row) use($primaryKeyColumn) {
            return $row[$primaryKeyColumn];
        }, $results);

        $results = array_combine($ids, $results);

        // Make a list of types appearing within this result set. 
        $discriminatorColumn = $this->entityMap->getDiscriminatorColumn();
        $types = array_pluck($results, $discriminatorColumn);

        // We'll split the result set by type that will make it easier to deal 
        // with.  
        $entities = [];
        foreach($types as $type) {
            $this->mappers[$type] = $this->getMapperForType($type);

            $resultsByType[$type] = array_filter($results, function(array $row) use($type, $discriminatorColumn) {
                return $row[$discriminatorColumn] === $type;
            });

            $entities = $entities + $this->buildResultsForType($resultsByType[$type], $type, $eagerLoads);
        }
        
        return array_map(function($id) use($entities) {
            return $entities[$id];
        }, $ids);
    }

    protected function buildResultsForType($results, $type, array $eagerLoads)
    {
        $builder = new ResultBuilder($this->mappers[$type]);
        return $builder->build($results, $eagerLoads); 
    }

    // /**
    //  * Cache result set.
    //  *
    //  * @param array $results
    //  *
    //  * @return void
    //  */
    // protected function cacheResultsByType(array $results, string $type)
    // {
    //     $mapper = $this->mappers[$type];
    //     $keyName = $mapper->getEntityMap()->getKeyName();
            
    //     // When hydrating EmbeddedValue object, they'll likely won't
    //     // have a primary key set.
    //     if(is_null($keyName)) {
    //         return;
    //     }

    //     $entityCache = $mapper->getEntityCache();
    //     $entityCache->add($results);
    // }

    // /**
    //  * Build embedded objects and match them to the result set.
    //  *
    //  * @param array $results
    //  *
    //  * @return array
    //  */
    // protected function buildEmbeddedRelationships(array $results) : array
    // {
    //     $entityMap = $this->entityMap;
    //     $instance = $this->defaultMapper->newInstance();
    //     $embeddeds = $entityMap->getEmbeddedRelationships();

    //     foreach ($embeddeds as $embedded) {
    //         $results = $entityMap->$embedded($instance)->match($results, $embedded);
    //     }

    //     return $results;
    // }

    // /**
    //  * Launch queries on eager loaded relationships.
    //  *
    //  * @param array $results
    //  * @param array $eagerLoads
    //  *
    //  * @return array
    //  */
    // protected function queryEagerLoadedRelationships(array $results, array $eagerLoads) : array
    // {
    //     $this->eagerLoads = $this->parseRelations($eagerLoads);

    //     return $this->eagerLoadRelations($results);
    // }

    // /**
    //  * Parse a list of relations into individuals.
    //  *
    //  * @param array $relations
    //  *
    //  * @return array
    //  */
    // protected function parseRelations(array $relations): array
    // {
    //     $results = [];

    //     foreach ($relations as $name => $constraints) {
    //         // If the "relation" value is actually a numeric key, we can assume that no
    //         // constraints have been specified for the eager load and we'll just put
    //         // an empty Closure with the loader so that we can treat all the same.
    //         if (is_numeric($name)) {
    //             $f = function () {
    //             };

    //             list($name, $constraints) = [$constraints, $f];
    //         }

    //         // We need to separate out any nested includes. Which allows the developers
    //         // to load deep relationships using "dots" without stating each level of
    //         // the relationship with its own key in the array of eager load names.
    //         $results = $this->parseNested($name, $results);

    //         $results[$name] = $constraints;
    //     }

    //     return $results;
    // }

    // /**
    //  * Parse the nested relationships in a relation.
    //  *
    //  * @param string $name
    //  * @param array  $results
    //  *
    //  * @return array
    //  */
    // protected function parseNested(string $name, array $results): array
    // {
    //     $progress = [];

    //     // If the relation has already been set on the result array, we will not set it
    //     // again, since that would override any constraints that were already placed
    //     // on the relationships. We will only set the ones that are not specified.
    //     foreach (explode('.', $name) as $segment) {
    //         $progress[] = $segment;

    //         if (!isset($results[$last = implode('.', $progress)])) {
    //             $results[$last] = function () {
    //             };
    //         }
    //     }

    //     return $results;
    // }

    // /**
    //  * Eager load the relationships on a result set.
    //  *
    //  * @param array $results
    //  *
    //  * @return array
    //  */
    // public function eagerLoadRelations(array $results): array
    // {
    //     foreach ($this->eagerLoads as $name => $constraints) {

    //         // For nested eager loads we'll skip loading them here and they will be set as an
    //         // eager load on the query to retrieve the relation so that they will be eager
    //         // loaded on that query, because that is where they get hydrated as models.
    //         if (strpos($name, '.') === false) {
    //             $results = $this->loadRelation($results, $name, $constraints);
    //         }
    //     }

    //     return $results;
    // }

    // /**
    //  * Eagerly load the relationship on a set of entities.
    //  *
    //  * @param array    $results
    //  * @param string   $name
    //  * @param \Closure $constraints
    //  *
    //  * @return array
    //  */
    // protected function loadRelation(array $results, string $name, Closure $constraints) : array
    // {
    //     // First we will "back up" the existing where conditions on the query so we can
    //     // add our eager constraints. Then we will merge the wheres that were on the
    //     // query back to it in order that any where conditions might be specified.
    //     $relation = $this->getRelation($name);

    //     $relation->addEagerConstraints($results);

    //     call_user_func($constraints, $relation);

    //     // Once we have the results, we just match those back up to their parent models
    //     // using the relationship instance. Then we just return the finished arrays
    //     // of models which have been eagerly hydrated and are readied for return.

    //     return $relation->match($results, $name);
    // }

    // /**
    //  * Get the relation instance for the given relation name.
    //  *
    //  * @param string $relation
    //  *
    //  * @return \Analogue\ORM\Relationships\Relationship
    //  */
    // public function getRelation(string $relation): Relationship
    // {
    //     // We want to run a relationship query without any constrains so that we will
    //     // not have to remove these where clauses manually which gets really hacky
    //     // and is error prone while we remove the developer's own where clauses.
    //     $query = Relationship::noConstraints(function () use ($relation) {
    //         return $this->entityMap->$relation($this->defaultMapper->newInstance());
    //     });

    //     $nested = $this->nestedRelations($relation);

    //     // If there are nested relationships set on the query, we will put those onto
    //     // the query instances so that they can be handled after this relationship
    //     // is loaded. In this way they will all trickle down as they are loaded.
    //     if (count($nested) > 0) {
    //         $query->getQuery()->with($nested);
    //     }

    //     return $query;
    // }

    // /**
    //  * Get the deeply nested relations for a given top-level relation.
    //  *
    //  * @param string $relation
    //  *
    //  * @return array
    //  */
    // protected function nestedRelations(string $relation): array
    // {
    //     $nested = [];

    //     // We are basically looking for any relationships that are nested deeper than
    //     // the given top-level relationship. We will just check for any relations
    //     // that start with the given top relations and adds them to our arrays.
    //     foreach ($this->eagerLoads as $name => $constraints) {
    //         if ($this->isNested($name, $relation)) {
    //             $nested[substr($name, strlen($relation.'.'))] = $constraints;
    //         }
    //     }

    //     return $nested;
    // }

    // /**
    //  * Determine if the relationship is nested.
    //  *
    //  * @param string $name
    //  * @param string $relation
    //  *
    //  * @return bool
    //  */
    // protected function isNested(string $name, string $relation): bool
    // {
    //     $dots = str_contains($name, '.');

    //     return $dots && starts_with($name, $relation.'.');
    // }

    // /**
    //  * Build an entity from results, using single table inheritance.
    //  *
    //  * @param array $results
    //  *
    //  * @return array
    //  */
    // protected function buildResultSet(array $results): array
    // {
    //     return array_map(function ($item) {
    //         $builder = $this->builderForResult($item);

    //         return $builder->build($item);
    //     }, $results);
    // }

    // /**
    //  * Given a result array, return the entity builder needed to correctly
    //  * build the result into an entity. If no getDiscriminatorColumnMap property
    //  * has been defined on the EntityMap, we'll assume that the value stored in
    //  * the $type column is the fully qualified class name of the entity and
    //  * we'll use it instead.
    //  *
    //  * @param array $result
    //  *
    //  * @return EntityBuilder
    //  */
    // protected function builderForResult(array $result): EntityBuilder
    // {
    //     $type = $result[$this->entityMap->getDiscriminatorColumn()];

    //     $mapper = $this->getMapperForSingleRow($result);

    //     if (!isset($this->builders[$type])) {
    //         $this->builders[$type] = new EntityBuilder(
    //             $mapper,
    //             array_keys($this->eagerLoads)
    //         );
    //     }

    //     return $this->builders[$type];
    // }

    // /**
    //  * Get mapper corresponding to the result type.
    //  *
    //  * @param array $result
    //  *
    //  * @return Mapper
    //  */
    // protected function getMapperForSingleRow(array $result) : Mapper
    // {
    //     $type = $result[$this->entityMap->getDiscriminatorColumn()];

    //     return $this->getMapperForType($type);
    // }

    protected function getMapperForType(string $type) : Mapper
    {
        $columnMap = $this->entityMap->getDiscriminatorColumnMap();

        $class = isset($columnMap[$type]) ? $columnMap[$type] : $type;

        return Manager::getInstance()->mapper($class);
    }
}