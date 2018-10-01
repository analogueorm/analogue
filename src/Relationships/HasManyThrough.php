<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\System\Query;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

class HasManyThrough extends Relationship
{
    /**
     * The distance parent Entity instance.
     *
     * @var \Analogue\ORM\Entity
     */
    protected $farParent;

    /**
     * The far parent map instance.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $farParentMap;

    /**
     * The near key on the relationship.
     *
     * @var string
     */
    protected $firstKey;

    /**
     * The far key on the relationship.
     *
     * @var string
     */
    protected $secondKey;

    /**
     * Create a new has many relationship instance.
     *
     * @param Mapper                  $mapper
     * @param \Analogue\ORM\Mappable  $farParent
     * @param \Analogue\ORM\EntityMap $parentMap
     * @param string                  $firstKey
     * @param string                  $secondKey
     *
     * @throws \Analogue\ORM\Exceptions\MappingException
     */
    public function __construct(Mapper $mapper, $farParent, $parentMap, $firstKey, $secondKey)
    {
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->farParent = $farParent;

        $this->farParentMap = $mapper->getManager()->mapper($farParent)->getEntityMap();
        $parentInstance = $mapper->getManager()->mapper($parentMap->getClass())->newInstance();

        parent::__construct($mapper, $parentInstance);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $parentTable = $this->parentMap->getTable();

        $this->setJoin();

        if (static::$constraints) {
            $farParentKeyName = $this->farParentMap->getKeyName();

            $this->query->where(
                $parentTable.'.'.$this->firstKey,
                '=',
                $this->farParent->getEntityAttribute($farParentKeyName)
            );
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
        $parentTable = $this->parentMap->getTable();

        $this->setJoin($query);

        $query->select(new Expression('count(*)'));

        $key = $this->wrap($parentTable.'.'.$this->firstKey);

        return $query->where($this->getHasCompareKey(), '=', new Expression($key));
    }

    /**
     * Set the join clause on the query.
     *
     * @param null|Query $query
     *
     * @return void
     */
    protected function setJoin(Query $query = null)
    {
        $query = $query ?: $this->query;

        $foreignKey = $this->relatedMap->getTable().'.'.$this->secondKey;

        $query->join($this->parentMap->getTable(), $this->getQualifiedParentKeyName(), '=', $foreignKey);
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
        $table = $this->parentMap->getTable();

        $this->query->whereIn($table.'.'.$this->firstKey, $this->getKeysFromResults($results));
    }

    /**
     * Match eagerly loaded relationship to a result set.
     *
     * @param array  $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $results, $relation)
    {
        $entities = $this->getEager();

        $dictionary = $this->buildDictionary($entities);

        $relatedKey = $this->relatedMap->getKeyName();

        $cache = $this->parentMapper->getEntityCache();

        $host = $this;

        // Once we have the dictionary we can simply spin through the parent entities to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        return array_map(function ($result) use ($relation, $relatedKey, $dictionary, $cache, $host) {
            $key = $result[$relatedKey];

            if (isset($dictionary[$key])) {
                $value = $host->relatedMap->newCollection($dictionary[$key]);

                $result[$relation] = $value;

                $cache->cacheLoadedRelationResult($key, $relation, $value, $this);
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
        $dictionary = [];

        $foreign = $this->firstKey;

        // First we will create a dictionary of entities keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // entities without having to do nested looping which will be quite slow.
        foreach ($results as $result) {
            $dictionary[$result->{$foreign}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the results of the relationship.
     *
     * @param  $relation
     *
     * @return EntityCollection
     */
    public function getResults($relation)
    {
        $results = $this->query->get();

        $this->cacheRelation($results, $relation);

        return $results;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return EntityCollection
     */
    public function get($columns = ['*']): Collection
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // entities with the result of those columns as a separate model relation.
        $select = $this->getSelectColumns($columns);

        $entities = $this->query->addSelect($select)->getEntities();

        // If we actually found entities we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($entities) > 0) {
            $entities = $this->query->eagerLoadRelations($entities);
        }

        return $this->relatedMap->newCollection($entities);
    }

    /**
     * Set the select clause for the relation query.
     *
     * @param array $columns
     *
     * @return BelongsToMany
     */
    protected function getSelectColumns(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            $columns = [$this->relatedMap->getTable().'.*'];
        }

        return array_merge($columns, [$this->parentMap->getTable().'.'.$this->firstKey]);
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'])
    {
        $this->query->addSelect($this->getSelectColumns($columns));

        return $this->query->paginate($perPage, $columns);
    }

    /**
     * Get the key name of the parent model.
     *
     * @return string
     */
    protected function getQualifiedParentKeyName()
    {
        return $this->parentMap->getQualifiedKeyName();
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getHasCompareKey()
    {
        return $this->farParentMap->getQualifiedKeyName();
    }

    /**
     * Run synchronization content if needed by the
     * relation type.
     *
     * @param array $entities
     *
     * @return void
     */
    public function sync(array $entities)
    {
        // N/A
    }
}
