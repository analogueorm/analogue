<?php

namespace Analogue\ORM\System;

use Analogue\ORM\Drivers\DBAdapter;
use Analogue\ORM\EntityCollection;
use Analogue\ORM\Exceptions\EntityNotFoundException;
use Analogue\ORM\LengthAwareEntityPaginator;
use Analogue\ORM\Relationships\Relationship;
use Closure;
use Exception;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Analogue Query builder.
 *
 * @mixin \Illuminate\Database\Query\Builder
 */
class Query
{
    /**
     * Mapper Instance.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $mapper;

    /**
     * DB Adatper.
     *
     * @var \Analogue\ORM\Drivers\DBAdapter
     */
    protected $adapter;

    /**
     * Query Builder Instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * Entity Map Instance.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $eagerLoad = [];

    /**
     * All of the registered builder macros.
     *
     * @var array
     */
    protected $macros = [];

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'cursor',
        'toSql',
        'lists',
        'pluck',
        'count',
        'min',
        'max',
        'avg',
        'sum',
        'exists',
        'getBindings',
    ];

    /**
     * Query Builder Blacklist.
     */
    protected $blacklist = [
        'insert',
        'insertGetId',
        'lock',
        'lockForUpdate',
        'sharedLock',
        'update',
        'increment',
        'decrement',
        'delete',
        'truncate',
        'raw',
    ];

    /**
     * Whether to use the mapper's entity caching.
     *
     * @var bool
     */
    protected $useCache = true;

    /**
     * Create a new Analogue Query Builder instance.
     *
     * @param Mapper    $mapper
     * @param DBAdapter $adapter
     */
    public function __construct(Mapper $mapper, DBAdapter $adapter)
    {
        $this->mapper = $mapper;

        $this->adapter = $adapter;

        $this->entityMap = $mapper->getEntityMap();

        // Specify the table to work on
        $this->query = $adapter->getQuery()->from($this->entityMap->getTable());

        $this->with($this->entityMap->getEagerloadedRelationships());
    }

    /**
     * Run the query and return the result.
     *
     * @param array $columns
     *
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*']): Collection
    {
        return $this->getEntities($columns);
    }

    /**
     * Find an entity by its primary key.
     *
     * @param string|int $id
     * @param array      $columns
     *
     * @return \Analogue\ORM\Mappable
     */
    public function find($id, $columns = ['*'])
    {
        if (is_array($id)) {
            return $this->findMany($id, $columns);
        }

        $this->query->where($this->entityMap->getKeyName(), '=', $id);

        return $this->first($columns);
    }

    /**
     * Find many entities by their primary keys.
     *
     * @param array $id
     * @param array $columns
     *
     * @return \Illuminate\Support\Collection
     */
    public function findMany($id, $columns = ['*'])
    {
        if (empty($id)) {
            return new EntityCollection();
        }

        $this->query->whereIn($this->entityMap->getKeyName(), $id);

        return $this->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @throws \Analogue\ORM\Exceptions\EntityNotFoundException
     *
     * @return mixed|self
     */
    public function findOrFail($id, $columns = ['*'])
    {
        if (!is_null($entity = $this->find($id, $columns))) {
            return $entity;
        }

        throw (new EntityNotFoundException())->setEntity(get_class($this->entityMap));
    }

    /**
     * Execute the query and get the first result.
     *
     * @param array $columns
     *
     * @return \Analogue\ORM\Entity
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $columns
     *
     * @throws EntityNotFoundException
     *
     * @return \Analogue\ORM\Entity
     */
    public function firstOrFail($columns = ['*'])
    {
        if (!is_null($entity = $this->first($columns))) {
            return $entity;
        }

        throw (new EntityNotFoundException())->setEntity(get_class($this->entityMap));
    }

    /**
     * Chunk the results of the query.
     *
     * @param int      $count
     * @param callable $callback
     *
     * @return void
     */
    public function chunk($count, callable $callback)
    {
        $results = $this->forPage($page = 1, $count)->get();

        while (count($results) > 0) {
            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            call_user_func($callback, $results);

            $page++;

            $results = $this->forPage($page, $count)->get();
        }
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string $key
     *
     * @return array
     */
    public function lists($column, $key = null)
    {
        return $this->query->pluck($column, $key);
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return LengthAwareEntityPaginator
     */
    public function paginate($perPage = null, $columns = ['*'])
    {
        $total = $this->query->getCountForPagination();

        $this->query->forPage(
            $page = Paginator::resolveCurrentPage(),
            $perPage = $perPage ?: $this->entityMap->getPerPage()
        );

        return new LengthAwareEntityPaginator($this->get($columns)->all(), $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * Get a paginator for a grouped statement.
     *
     * @param \Illuminate\Pagination\Factory $paginator
     * @param int                            $perPage
     * @param array                          $columns
     *
     * @return \Illuminate\Pagination\Paginator
     */
    protected function groupedPaginate($paginator, $perPage, $columns)
    {
        $results = $this->get($columns)->all();

        return $this->query->buildRawPaginator($paginator, $results, $perPage);
    }

    /**
     * Get a paginator for an ungrouped statement.
     *
     * @param \Illuminate\Pagination\Factory $paginator
     * @param int                            $perPage
     * @param array                          $columns
     *
     * @return \Illuminate\Pagination\Paginator
     */
    protected function ungroupedPaginate($paginator, $perPage, $columns)
    {
        $total = $this->query->getPaginationCount();

        // Once we have the paginator we need to set the limit and offset values for
        // the query so we can get the properly paginated items. Once we have an
        // array of items we can create the paginator instances for the items.
        $page = $paginator->getCurrentPage($total);

        $this->query->forPage($page, $perPage);

        return $paginator->make($this->get($columns)->all(), $total, $perPage);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'])
    {
        $page = Paginator::resolveCurrentPage();

        $perPage = $perPage ?: $this->entityMap->getPerPage();

        $this->skip(($page - 1) * $perPage)->take($perPage + 1);

        return new Paginator($this->get($columns)->all(), $perPage, $page, ['path' => Paginator::resolveCurrentPath()]);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $boolean
     *
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure) {
            $query = $this->newQueryWithoutScopes();

            call_user_func($column, $query);

            $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
        } else {
            call_user_func_array([$this->query, 'where'], func_get_args());
        }

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return \Analogue\ORM\System\Query
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a relationship count condition to the query.
     *
     * @param string   $relation
     * @param string   $operator
     * @param int      $count
     * @param string   $boolean
     * @param \Closure $callback
     *
     * @return \Analogue\ORM\System\Query
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        $entity = $this->mapper->newInstance();

        $relation = $this->getHasRelationQuery($relation, $entity);

        $query = $relation->getRelationCountQuery($relation->getRelatedMapper()->getQuery(), $this);

        if ($callback) {
            call_user_func($callback, $query);
        }

        return $this->addHasWhere($query, $relation, $operator, $count, $boolean);
    }

    /**
     * Add a relationship count condition to the query with where clauses.
     *
     * @param string   $relation
     * @param \Closure $callback
     * @param string   $operator
     * @param int      $count
     *
     * @return \Analogue\ORM\System\Query
     */
    public function whereHas($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'and', $callback);
    }

    /**
     * Add a relationship count condition to the query with an "or".
     *
     * @param string $relation
     * @param string $operator
     * @param int    $count
     *
     * @return \Analogue\ORM\System\Query
     */
    public function orHas($relation, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or');
    }

    /**
     * Add a relationship count condition to the query with where clauses and an "or".
     *
     * @param string   $relation
     * @param \Closure $callback
     * @param string   $operator
     * @param int      $count
     *
     * @return \Analogue\ORM\System\Query
     */
    public function orWhereHas($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or', $callback);
    }

    /**
     * Add the "has" condition where clause to the query.
     *
     * @param \Analogue\ORM\System\Query               $hasQuery
     * @param \Analogue\ORM\Relationships\Relationship $relation
     * @param string                                   $operator
     * @param int                                      $count
     * @param string                                   $boolean
     *
     * @return \Analogue\ORM\System\Query
     */
    protected function addHasWhere(self $hasQuery, Relationship $relation, $operator, $count, $boolean)
    {
        $this->mergeWheresToHas($hasQuery, $relation);

        if (is_numeric($count)) {
            $count = new Expression($count);
        }

        return $this->where(new Expression('('.$hasQuery->toSql().')'), $operator, $count, $boolean);
    }

    /**
     * Merge the "wheres" from a relation query to a has query.
     *
     * @param \Analogue\ORM\System\Query               $hasQuery
     * @param \Analogue\ORM\Relationships\Relationship $relation
     *
     * @return void
     */
    protected function mergeWheresToHas(self $hasQuery, Relationship $relation)
    {
        // Here we have the "has" query and the original relation. We need to copy over any
        // where clauses the developer may have put in the relationship function over to
        // the has query, and then copy the bindings from the "has" query to the main.
        $relationQuery = $relation->getBaseQuery();

        $hasQuery->mergeWheres(
            $relationQuery->wheres,
            $relationQuery->getBindings()
        );

        $this->query->mergeBindings($hasQuery->getQuery());
    }

    /**
     * Get the "has relation" base query instance.
     *
     * @param string $relation
     * @param        $entity
     *
     * @return \Analogue\ORM\System\Query
     */
    protected function getHasRelationQuery($relation, $entity)
    {
        return Relationship::noConstraints(function () use ($relation, $entity) {
            return $this->entityMap->$relation($entity);
        });
    }

    /**
     * Disable loading results from the entity instance cache.
     *
     * Loaded entities will still be stored in the cache.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function disableCache()
    {
        $this->useCache = false;

        return $this;
    }

    /**
     * Enable loading results from the entity instance cache.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function enableCache()
    {
        $this->useCache = true;

        return $this;
    }

    /**
     * Get the table for the current query object.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->entityMap->getTable();
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param mixed $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $this->eagerLoad = array_merge($this->eagerLoad, $relations);

        return $this;
    }

    /**
     * Get the relationships being eagerly loaded.
     *
     * @return array
     */
    public function getEagerLoads()
    {
        return $this->eagerLoad;
    }

    /**
     * Add the Entity primary key if not in requested columns.
     *
     * @param array $columns
     *
     * @return array
     */
    protected function enforceIdColumn($columns)
    {
        $primaryKey = $this->entityMap->getKeyName();
        $escapedKeyName = preg_quote($primaryKey, '/');
        $table = $this->entityMap->getTable();

        $match = false;

        foreach ($columns as $column) {
            if (substr($column, -strlen($primaryKey)) === $primaryKey) {
                if (strlen($column) === strlen($primaryKey)) {
                    $match = true;
                    break;
                } elseif (preg_match("/\w+\s+(?:(?:(AS|as)\s+)?$escapedKeyName/", $column)) {
                    $match = true;
                    break;
                }
            }
        }

        if (!$match) {
            $columns[] = "$table.$primaryKey AS $primaryKey";
        }

        return $columns;
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param array $columns
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEntities($columns = ['*'])
    {
        // As we need the primary key to feed the
        // entity cache, we need it loaded on each
        // request
        if ($columns !== ['*']) {
            $columns = $this->enforceIdColumn($columns);
        }

        // Run the query
        $results = $this->query->get($columns);

        // Pass result set to the mapper and return the EntityCollection
        return $this->mapper->map($results, $this->getEagerLoads(), $this->useCache);
    }

    /**
     * Extend the builder with a given callback.
     *
     * @param string   $name
     * @param \Closure $callback
     *
     * @return void
     */
    public function macro($name, Closure $callback)
    {
        $this->macros[$name] = $callback;
    }

    /**
     * Get the given macro by name.
     *
     * @param string $name
     *
     * @return \Closure
     */
    public function getMacro($name)
    {
        return array_get($this->macros, $name);
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function newQuery()
    {
        $builder = new self($this->mapper, $this->adapter);

        return $this->applyGlobalScopes($builder);
    }

    /**
     * Get a new query builder without any scope applied.
     *
     * @return \Analogue\ORM\System\Query
     */
    public function newQueryWithoutScopes()
    {
        return new self($this->mapper, $this->adapter);
    }

    /**
     * Get the Mapper instance for this Query Builder.
     *
     * @return \Analogue\ORM\System\Mapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Get the underlying query adapter.
     *
     * (REFACTOR: this method should move out, we need to provide the client classes
     * with the adapter instead.)
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (isset($this->macros[$method])) {
            array_unshift($parameters, $this);

            return call_user_func_array($this->macros[$method], $parameters);
        }

        if (in_array($method, $this->blacklist)) {
            throw new Exception("Method $method doesn't exist");
        }

        $result = call_user_func_array([$this->query, $method], $parameters);

        return in_array($method, $this->passthru) ? $result : $this;
    }
}
