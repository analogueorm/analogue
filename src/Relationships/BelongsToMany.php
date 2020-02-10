<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\Exceptions\EntityNotFoundException;
use Analogue\ORM\Mappable;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Query;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

class BelongsToMany extends Relationship
{
    /**
     * The intermediate table for the relation.
     *
     * @var string
     */
    protected $table;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key of the relation.
     *
     * @var string
     */
    protected $otherKey;

    /**
     * The "name" of the relationship.
     *
     * @var string
     */
    protected $relation;

    /**
     * The pivot table columns to retrieve.
     *
     * @var array
     */
    protected $pivotColumns = [];

    /**
     * This relationship has pivot attributes.
     *
     * @var bool
     */
    protected static $hasPivot = true;

    /**
     * Create a new has many relationship instance.
     *
     * @param Mapper   $mapper
     * @param Mappable $parent
     * @param string   $table
     * @param string   $foreignKey
     * @param string   $otherKey
     * @param string   $relation
     */
    public function __construct(Mapper $mapper, $parent, $table, $foreignKey, $otherKey, $relation)
    {
        $this->table = $table;
        $this->otherKey = $otherKey;
        $this->foreignKey = $foreignKey;
        $this->relation = $relation;

        parent::__construct($mapper, $parent);
    }

    /**
     * @param $related
     */
    public function detachMany($related)
    {
        $ids = $this->getIdsFromHashes($related);

        $this->detach($ids);
    }

    /**
     * @param array $hashes
     *
     * @return array
     */
    protected function getIdsFromHashes(array $hashes)
    {
        $ids = [];

        foreach ($hashes as $hash) {
            $split = explode('.', $hash);
            $ids[] = $split[1];
        }

        return $ids;
    }

    /**
     * Get the results of the relationship.
     *
     * @param $relation
     *
     * @return EntityCollection
     */
    public function getResults($relation)
    {
        $results = $this->get();

        $this->cacheRelation($results, $relation);

        return $results;
    }

    /**
     * Set a where clause for a pivot table column.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $boolean
     *
     * @return self
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->where($this->table.'.'.$column, $operator, $value, $boolean);
    }

    /**
     * Set an or where clause for a pivot table column.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return self
     */
    public function orWherePivot($column, $operator = null, $value = null)
    {
        return $this->wherePivot($column, $operator, $value, 'or');
    }

    /**
     * Return Pivot attributes when available on a relationship.
     *
     * @return array
     */
    public function getPivotAttributes()
    {
        return $this->pivotColumns;
    }

    /**
     * Execute the query and get the first result.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $columns
     *
     * @throws EntityNotFoundException
     *
     * @return Mappable|self
     */
    public function firstOrFail($columns = ['*'])
    {
        if (!is_null($entity = $this->first($columns))) {
            return $entity;
        }

        throw new EntityNotFoundException();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*']): Collection
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $columns = $this->query->getQuery()->columns ? [] : $columns;

        $select = $this->getSelectColumns($columns);

        $entities = $this->query->addSelect($select)->disableCache()->get()->all();

        $entities = $this->hydratePivotRelation($entities);

        return $this->relatedMap->newCollection($entities);
    }

    /**
     * Hydrate the pivot table relationship on the models.
     *
     * @param array $entities
     *
     * @return array
     */
    protected function hydratePivotRelation(array $entities)
    {
        // TODO (note) We should definitely get rid of the pivot in a next
        // release, as this is not quite relevant in a datamapper context.
        return array_map(function ($entity) {
            $entityWrapper = $this->factory->make($entity);

            $pivotAttributes = $this->cleanPivotAttributes($entityWrapper);
            $pivot = $this->newExistingPivot($pivotAttributes);
            $entityWrapper->setEntityAttribute('pivot', $pivot);

            $object = $entityWrapper->unwrap();

            return $object;
        }, $entities);
    }

    /**
     * Get the pivot attributes from a model.
     *
     * @param InternallyMappable $entity
     *
     * @return array
     */
    protected function cleanPivotAttributes(InternallyMappable $entity)
    {
        $values = [];

        $attributes = $entity->getEntityAttributes();

        foreach ($attributes as $key => $value) {
            // To get the pivots attributes we will just take any of the attributes which
            // begin with "pivot_" and add those to this arrays, as well as unsetting
            // them from the parent's models since they exist in a different table.
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($attributes[$key]);
            }
        }

        // Rehydrate Entity with cleaned array.
        $entity->setEntityAttributes($attributes);

        return $values;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $this->setJoin();

        if (static::$constraints) {
            $this->setWhere();
        }
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
        if ($parent->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationCountQueryForSelfJoin($query, $parent);
        }

        $this->setJoin($query);

        return parent::getRelationCountQuery($query, $parent);
    }

    /**
     * Add the constraints for a relationship count query on the same table.
     *
     * @param Query $query
     * @param Query $parent
     *
     * @return Query
     */
    public function getRelationCountQueryForSelfJoin(Query $query, Query $parent)
    {
        $query->select(new Expression('count(*)'));

        $tablePrefix = $this->query->getQuery()->getConnection()->getTablePrefix();

        $query->from($this->table.' as '.$tablePrefix.$hash = $this->getRelationCountHash());

        $key = $this->wrap($this->getQualifiedParentKeyName());

        return $query->where($hash.'.'.$this->foreignKey, '=', new Expression($key));
    }

    /**
     * Get a relationship join table hash.
     *
     * @return string
     */
    public function getRelationCountHash()
    {
        return 'self_'.md5(microtime(true));
    }

    /**
     * Set the select clause for the relation query.
     *
     * @param array $columns
     *
     * @return \Analogue\ORM\Relationships\BelongsToMany
     */
    protected function getSelectColumns(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            $columns = [$this->relatedMap->getTable().'.*'];
        }

        return array_merge($columns, $this->getAliasedPivotColumns());
    }

    /**
     * Get the pivot columns for the relation.
     *
     * @return array
     */
    protected function getAliasedPivotColumns()
    {
        $defaults = [$this->foreignKey, $this->otherKey];

        // We need to alias all of the pivot columns with the "pivot_" prefix so we
        // can easily extract them out of the models and put them into the pivot
        // relationships when they are retrieved and hydrated into the models.
        $columns = [];

        foreach (array_merge($defaults, $this->pivotColumns) as $column) {
            $columns[] = $this->table.'.'.$column.' as pivot_'.$column;
        }

        return array_unique($columns);
    }

    /**
     * Set the join clause for the relation query.
     *
     * @param  \Analogue\ORM\Query|null
     *
     * @return $this
     */
    protected function setJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $baseTable = $this->relatedMap->getTable();

        $key = $baseTable.'.'.$this->relatedMap->getKeyName();

        $query->join($this->table, $key, '=', $this->getOtherKey());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function setWhere()
    {
        $foreign = $this->getForeignKey();

        $parentKey = $this->parentMap->getKeyName();

        $this->query->where($foreign, '=', $this->parent->getEntityAttribute($parentKey));

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $results
     *
     * @return void
     */
    public function addEagerConstraints(array $results)
    {
        $this->query->whereIn($this->getForeignKey(), $this->getKeysFromResults($results));
    }

    /**
     * Match Eagerly loaded relation to result.
     *
     * @param array  $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $results, $relation)
    {
        $entities = $this->getEager();

        // TODO; optimize this operation
        $dictionary = $this->buildDictionary($entities);

        $keyName = $this->parentMap->getKeyName();

        $cache = $this->parentMapper->getEntityCache();

        $host = $this;

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
        return array_map(function ($result) use ($dictionary, $keyName, $cache, $relation, $host) {
            $key = $result[$keyName];

            if (isset($dictionary[$key])) {
                $collection = $host->relatedMap->newCollection($dictionary[$key]);

                $result[$relation] = $collection;

                // TODO Refactor this
                $cache->cacheLoadedRelationResult($key, $relation, $collection, $this);
            } else {
                $result[$relation] = $host->relatedMap->newCollection();
            }

            return $result;
        }, $results);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param EntityCollection $results
     *
     * @return array
     */
    protected function buildDictionary(EntityCollection $results)
    {
        $foreign = $this->foreignKey;

        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
        $dictionary = [];

        foreach ($results as $entity) {
            $wrapper = $this->factory->make($entity);

            $foreignKey = $wrapper->getEntityAttribute('pivot')->$foreign;

            $dictionary[$foreignKey][] = $entity;
        }

        return $dictionary;
    }

    /**
     * Get all of the IDs for the related models.
     *
     * @return array
     */
    public function getRelatedIds()
    {
        $fullKey = $this->relatedMap->getQualifiedKeyName();

        return $this->getQuery()->select($fullKey)->lists($this->relatedMap->getKeyName());
    }

    /**
     * Update Pivot.
     *
     * @param \Analogue\ORM\Entity $entity
     *
     * @return void
     */
    public function updatePivot($entity)
    {
        $keyName = $this->relatedMap->getKeyName();

        $this->updateExistingPivot(
            $entity->getEntityAttribute($keyName),
            $entity->getEntityAttribute('pivot')->getEntityAttributes()
        );
    }

    /**
     * Update Multiple pivot.
     *
     * @param  $relatedEntities
     *
     * @return void
     */
    public function updatePivots($relatedEntities)
    {
        foreach ($relatedEntities as $entity) {
            $this->updatePivot($entity);
        }
    }

    /**
     * Create Pivot Records.
     *
     * @param \Analogue\ORM\Entity[] $relatedEntities
     *
     * @return void
     */
    public function createPivots($relatedEntities)
    {
        $keys = [];
        $attributes = [];

        $keyName = $this->relatedMap->getKeyName();

        foreach ($relatedEntities as $entity) {
            $keys[] = $entity->getEntityAttribute($keyName);
        }

        $records = $this->createAttachRecords($keys, $attributes);

        $this->query->getQuery()->from($this->table)->insert($records);
    }

    /**
     * Update an existing pivot record on the table.
     *
     * @param mixed $id
     * @param array $attributes
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    public function updateExistingPivot($id, array $attributes)
    {
        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            $attributes = $this->setTimestampsOnAttach($attributes, true);
        }

        return $this->newPivotStatementForId($id)->update($attributes);
    }

    /**
     * Attach a model to the parent.
     *
     * @param mixed $id
     * @param array $attributes
     *
     * @return void
     */
    public function attach($id, array $attributes = [])
    {
        $query = $this->newPivotStatement();

        $query->insert($this->createAttachRecords((array) $id, $attributes));
    }

    /**
     * @param array $entities
     *
     * @throws \InvalidArgumentException
     */
    public function sync(array $entities)
    {
        $this->detachExcept($entities);
    }

    /**
     * Detach related entities that are not in $id.
     *
     * @param array $entities
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function detachExcept(array $entities = [])
    {
        $query = $this->newPivotQuery();

        if (count($entities) > 0) {
            $keys = $this->getKeys($entities);

            $query->whereNotIn($this->otherKey, $keys);
        }
        $parentKey = $this->parentMap->getKeyName();

        $query->where($this->foreignKey, '=', $this->parent->getEntityAttribute($parentKey));

        $query->delete();
    }

    /**
     * Create an array of records to insert into the pivot table.
     *
     * @param array $ids
     * @param array $attributes
     *
     * @return array
     */
    protected function createAttachRecords($ids, array $attributes)
    {
        $records = [];

        $timed = in_array($this->createdAt(), $this->pivotColumns);

        // To create the attachment records, we will simply spin through the IDs given
        // and create a new record to insert for each ID. Each ID may actually be a
        // key in the array, with extra attributes to be placed in other columns.
        foreach ($ids as $key => $value) {
            $records[] = $this->attacher($key, $value, $attributes, $timed);
        }

        return $records;
    }

    /**
     * Create a full attachment record payload.
     *
     * @param int   $key
     * @param mixed $value
     * @param array $attributes
     * @param bool  $timed
     *
     * @return array
     */
    protected function attacher($key, $value, $attributes, $timed)
    {
        list($id, $extra) = $this->getAttachId($key, $value, $attributes);

        // To create the attachment records, we will simply spin through the IDs given
        // and create a new record to insert for each ID. Each ID may actually be a
        // key in the array, with extra attributes to be placed in other columns.
        $record = $this->createAttachRecord($id, $timed);

        return array_merge($record, $extra);
    }

    /**
     * Get the attach record ID and extra attributes.
     *
     * @param int   $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return array
     */
    protected function getAttachId($key, $value, array $attributes)
    {
        if (is_array($value)) {
            return [$key, array_merge($value, $attributes)];
        }

        return [$value, $attributes];
    }

    /**
     * Create a new pivot attachment record.
     *
     * @param int  $id
     * @param bool $timed
     *
     * @return array
     */
    protected function createAttachRecord($id, $timed)
    {
        $parentKey = $this->parentMap->getKeyName();

        $record = [];

        $record[$this->foreignKey] = $this->parent->getEntityAttribute($parentKey);

        $record[$this->otherKey] = $id;

        // If the record needs to have creation and update timestamps, we will make
        // them by calling the parent model's "freshTimestamp" method which will
        // provide us with a fresh timestamp in this model's preferred format.
        if ($timed) {
            $record = $this->setTimestampsOnAttach($record);
        }

        return $record;
    }

    /**
     * Set the creation and update timestamps on an attach record.
     *
     * @param array $record
     * @param bool  $exists
     *
     * @return array
     */
    protected function setTimestampsOnAttach(array $record, $exists = false)
    {
        $fresh = $this->freshTimestamp();

        if (!$exists) {
            $record[$this->createdAt()] = $fresh;
        }

        $record[$this->updatedAt()] = $fresh;

        return $record;
    }

    /**
     * @param EntityCollection $entities
     *
     * @return array
     */
    protected function getModelKeysFromCollection(EntityCollection $entities)
    {
        $keyName = $this->relatedMap->getKeyName();

        return array_map(function ($m) use ($keyName) {
            return $m->$keyName;
        }, $entities);
    }

    /**
     * Detach models from the relationship.
     *
     * @param int|array $ids
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    public function detach($ids = [])
    {
        if ($ids instanceof EntityCollection) {
            $ids = (array) $ids->modelKeys();
        }

        $query = $this->newPivotQuery();

        // If associated IDs were passed to the method we will only delete those
        // associations, otherwise all of the association ties will be broken.
        // We'll return the numbers of affected rows when we do the deletes.
        $ids = (array) $ids;

        if (count($ids) > 0) {
            $query->whereIn($this->otherKey, (array) $ids);
        }

        // Once we have all of the conditions set on the statement, we are ready
        // to run the delete on the pivot table. Then, if the touch parameter
        // is true, we will go ahead and touch all related models to sync.
        return $query->delete();
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery()
    {
        $query = $this->newPivotStatement();

        $parentKey = $this->parentMap->getKeyName();

        return $query->where($this->foreignKey, $this->parent->getEntityAttribute($parentKey));
    }

    /**
     * Get a new plain query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotStatement()
    {
        return $this->query->getQuery()->newQuery()->from($this->table);
    }

    /**
     * Get a new pivot statement for a given "other" ID.
     *
     * @param mixed $id
     *
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotStatementForId($id)
    {
        $pivot = $this->newPivotStatement();

        $parentKeyName = $this->parentMap->getKeyName();

        $key = $this->parent->getEntityAttribute($parentKeyName);

        return $pivot->where($this->foreignKey, $key)->where($this->otherKey, $id);
    }

    /**
     * Create a new pivot model instance.
     *
     * @param array $attributes
     * @param bool  $exists
     *
     * @return \Analogue\ORM\Relationships\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $pivot = new Pivot($this->parent, $this->parentMap, $attributes, $this->table, $exists);

        return $pivot->setPivotKeys($this->foreignKey, $this->otherKey);
    }

    /**
     * Create a new existing pivot model instance.
     *
     * @param array $attributes
     *
     * @return \Analogue\ORM\Relationships\Pivot
     */
    public function newExistingPivot(array $attributes = [])
    {
        return $this->newPivot($attributes, true);
    }

    /**
     * Set the columns on the pivot table to retrieve.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function withPivot($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->pivotColumns = array_merge($this->pivotColumns, $columns);

        return $this;
    }

    /**
     * Specify that the pivot table has creation and update timestamps.
     *
     * @param mixed $createdAt
     * @param mixed $updatedAt
     *
     * @return \Analogue\ORM\Relationships\BelongsToMany
     */
    public function withTimestamps($createdAt = null, $updatedAt = null)
    {
        return $this->withPivot($createdAt ?: $this->createdAt(), $updatedAt ?: $this->updatedAt());
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getHasCompareKey()
    {
        return $this->getForeignKey();
    }

    /**
     * Get the fully qualified foreign key for the relation.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->table.'.'.$this->foreignKey;
    }

    /**
     * Get the fully qualified "other key" for the relation.
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->table.'.'.$this->otherKey;
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    protected function getQualifiedParentKeyName()
    {
        return $this->parentMap->getQualifiedKeyName();
    }

    /**
     * Get the intermediate table for the relationship.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the relationship name for the relationship.
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relation;
    }
}
