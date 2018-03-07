<?php

namespace Analogue\ORM\System\Proxies;

use Analogue\ORM\EntityCollection;
use Analogue\ORM\Relationships\Relationship;
use Analogue\ORM\System\Manager;
use CachingIterator;
use Illuminate\Support\Collection;
use ProxyManager\Proxy\ProxyInterface;

class CollectionProxy extends EntityCollection implements ProxyInterface
{
    /**
     * Indicate if the relationship has been lazy loaded.
     *
     * @var bool
     */
    protected $relationshipLoaded = false;

    /**
     * Added items.
     *
     * @var array
     */
    protected $addedItems = [];

    /**
     * Parent entity.
     *
     * @var \Analogue\ORM\Mappable|string
     */
    protected $parentEntity;

    /**
     * Relationship.
     *
     * @var string
     */
    protected $relationshipMethod;

    /**
     * Create a new collection.
     *
     * @param mixed  $entity
     * @param string $relation
     */
    public function __construct($entity, $relation)
    {
        $this->parentEntity = $entity;
        $this->relationshipMethod = $relation;
        parent::__construct();
    }

    /**
     * Return Items that has been added without lazy loading
     * the underlying collection.
     *
     * @return array
     */
    public function getAddedItems()
    {
        return $this->addedItems;
    }

    /**
     * Force initialization of the proxy.
     *
     * @return bool true if the proxy could be initialized
     */
    public function initializeProxy() : bool
    {
        if ($this->isProxyInitialized()) {
            return true;
        }

        $this->items = $this->getRelationshipInstance()
            ->getResults($this->relationshipMethod)->all() + $this->addedItems;

        $this->relationshipLoaded = true;

        return true;
    }

    /**
     * Return instance of the underlying relationship.
     *
     * @return Relationship
     */
    protected function getRelationshipInstance() : Relationship
    {
        $relation = $this->relationshipMethod;
        $entity = $this->parentEntity;
        $entityMap = Manager::getMapper($entity)->getEntityMap();

        return $entityMap->$relation($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isProxyInitialized() : bool
    {
        return $this->relationshipLoaded;
    }

    /**
     * {@inheritdoc}
     */
    protected function toBaseCollection() : Collection
    {
        return new Collection($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        $this->initializeProxy();

        return parent::all();
    }

    /**
     * {@inheritdoc}
     */
    public function avg($callback = null)
    {
        $this->initializeProxy();

        return parent::avg($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function median($key = null)
    {
        $this->initializeProxy();

        return parent::median($key);
    }

    /**
     * {@inheritdoc}
     */
    public function mode($key = null)
    {
        $this->initializeProxy();

        return parent::mode($key);
    }

    /**
     * {@inheritdoc}
     */
    public function collapse()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->collapse();
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key, $operator = null, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        switch (func_num_args()) {
            case 1:
                return $parent->contains($key);
            case 2:
                return $parent->contains($key, $operator);
            case 3:
                return $parent->contains($key, $operator, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function containsStrict($key, $value = null)
    {
        $this->initializeProxy();

        switch (func_num_args()) {
            case 1:
                return parent::containsStrict($key);
            case 2:
                return parent::containsStrict($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function diff($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diff($items);
    }

    /**
     * {@inheritdoc}
     */
    public function diffUsing($items, callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffUsing($items, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function diffAssocUsing($items, callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffAssocUsing($items, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function diffKeys($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffKeys($items);
    }

    /**
     * {@inheritdoc}
     */
    public function diffKeysUsing($items, callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffKeysUsing($items);
    }

    /**
     * {@inheritdoc}
     */
    public function each(callable $callback)
    {
        $this->initializeProxy();

        return parent::each($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function every($key, $operator = null, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        switch (func_num_args()) {
            case 1:
                return $parent->every($key);
            case 2:
                return $parent->every($key, $operator);
            case 3:
                return $parent->every($key, $operator, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function except($keys)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->except($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $callback = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->filter($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function firstWhere($key, $operator, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->filterWhere($key, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function where($key, $operator, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->where($key, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function whereInstanceOf($type)
    {
        $this->initializeProxy();

        return parent::whereInstanceOf($type);
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereNotIn($key, $values, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function whereStrict($key, $value)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereStrict($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function whereIn($key, $values, $strict = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereIn($key, $values, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function whereInStrict($key, $values)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereInStrict($key, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function first(callable $callback = null, $default = null)
    {
        // TODO Consider partial loading
        $this->initializeProxy();

        return parent::first($callback, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function flatten($depth = INF)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->flatten($depth);
    }

    /**
     * {@inheritdoc}
     */
    public function flip()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->flip();
    }

    /**
     * {@inheritdoc}
     */
    public function forget($keys)
    {
        // TODO, we could consider these as
        // 'pending deletion', the same way that
        // we treat added items
        $this->initializeProxy();

        return parent::forget($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        // TODO : We could also consider partial loading
        // here
        $this->initializeProxy();

        return parent::get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->groupBy($groupBy, $preserveKeys);
    }

    /**
     * {@inheritdoc}
     */
    public function keyBy($keyBy)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->keyBy($keyBy);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        // TODO : we could do automagic here by directly
        // calling the database if the collection hasn't
        // been initialized yet.
        // Potential issue is that several calls to this
        // could cause a lot queries vs a single get query.
        $this->initializeProxy();

        return parent::has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function implode($value, $glue = null)
    {
        $this->initializeProxy();

        return parent::implode($value, $glue);
    }

    /**
     * {@inheritdoc}
     */
    public function intersect($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->intersect($items);
    }

    /**
     * {@inheritdoc}
     */
    public function intersectByKeys($items)
    {
        $this->initializeProxy();

        return parent::intersectByKeys($items);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->initializeProxy();

        return parent::isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function last(callable $callback = null, $default = null)
    {
        // TODO : we could do partial loading there as well
        $this->initializeProxy();

        return parent::last($callback, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function pluck($value, $key = null)
    {
        // TODO : automagic call to QB if not initialized
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->pluck($value, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->map($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function mapInto($class)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->map($class);
    }

    /**
     * {@inheritdoc}
     */
    public function mapWithKeys(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->mapWithKeys($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function mapToDictionary(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->mapWithKeys($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function flatMap(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->flatMap($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function max($callback = null)
    {
        $this->initializeProxy();

        return parent::max($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->merge($items);
    }

    /**
     * {@inheritdoc}
     */
    public function pad($size, $value)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent($size, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function combine($values)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->combine($values);
    }

    /**
     * {@inheritdoc}
     */
    public static function times($amount, callable $callback = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->times($amount, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function crossJoin(...$lists)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->times(func_num_args());
    }

    /**
     * {@inheritdoc}
     */
    public function diffAssoc($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffAssoc($items);
    }

    /**
     * {@inheritdoc}
     */
    public function intersectKey($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->intersectKey($items);
    }

    /**
     * {@inheritdoc}
     */
    public function union($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->union($items);
    }

    /**
     * {@inheritdoc}
     */
    public function min($callback = null)
    {
        // TODO : we could rely on the QB
        // for this, if initialization has not
        // take place yet
        $this->initializeProxy();

        return parent::min($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function nth($step, $offset = 0)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->nth($step, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function only($keys)
    {
        // TODO : we could rely on the QB if
        // the collection hasn't been initialized yet
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->only($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function forPage($page, $perPage)
    {
        // TODO : check possibility of partial loading
        // if not initialized
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->forPage($page, $perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function partition($callback, $operator = null, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        switch (func_num_args()) {
            case 1:
                return $parent->partition($callback);
            case 2:
                return $parent->partition($callback, $operator);
            case 3:
                return $parent->partition($callback, $operator, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function pipe(callable $callback)
    {
        $this->initializeProxy();

        return parent::pipe($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        $this->initializeProxy();

        return parent::pop();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend($value, $key = null)
    {
        // TODO : partial adding of values.
        // we could have a $prepended , and $pushed arrays
        // which we would combine at full initialization

        $this->initializeProxy();

        return parent::prepend($value, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function push($value)
    {
        // TODO : partial adding of values.
        // we could have a $prepended , and $pushed arrays
        // which we would combine at full initialization

        $this->initializeProxy();

        return parent::push($value);
    }

    /**
     * {@inheritdoc}
     */
    public function pull($key, $default = null)
    {
        // TODO : QB query if the collection
        // hasn't been initialized yet

        $this->initializeProxy();

        return parent::pull($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value)
    {
        // TODO : Partial loading ?

        $this->initializeProxy();

        return parent::put($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function random($amount = null)
    {
        // TODO : we could optimize this by only
        // fetching the keys from the database
        // and performing partial loading

        $this->initializeProxy();

        return parent::random($amount);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce(callable $callback, $initial = null)
    {
        $this->initializeProxy();

        return parent::reduce($callback, $initial);
    }

    /**
     * {@inheritdoc}
     */
    public function reject($callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->reject($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function reverse()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->reverse();
    }

    /**
     * {@inheritdoc}
     */
    public function search($value, $strict = false)
    {
        $this->initializeProxy();

        return parent::search($value, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function shift()
    {
        // Todo : Partial Removing
        // we could have a pending removal array
        $this->initializeProxy();

        return parent::shift();
    }

    /**
     * {@inheritdoc}
     */
    public function shuffle($seed = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->shuffle($seed);
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->slice($offset, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function split($numberOfGroups)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->split($numberOfGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function chunk($size)
    {
        // TODO : partial loading ?
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->chunk($size);
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $callback = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->sort($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->sortBy($callback, $options, $descending);
    }

    /**
     * {@inheritdoc}
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->sortKeys($options, $descending);
    }

    /**
     * {@inheritdoc}
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        switch (func_num_args()) {
            case 1:
                $parent->splice($offset);
            case 2:
                $parent->splice($offset, $length);
            case 3:
                $parent->splice($offset, $length, $replacement);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sum($callback = null)
    {
        $this->initializeProxy();

        return parent::sum($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function take($limit)
    {
        // TODO: partial loading
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->take($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function tap(callable $callback)
    {
        $this->initializeProxy();

        return parent::tap($this);
    }

    /**
     * {@inheritdoc}
     */
    public function transform(callable $callback)
    {
        $this->initializeProxy();

        return parent::transform($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function unique($key = null, $strict = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->unique($key, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function values()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->values();
    }

    /**
     * {@inheritdoc}
     */
    public function when($value, callable $callback, callable $default = null)
    {
        $this->initializeProxy();

        return parent::when($value, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function zip($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->zip($items);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        // If this is called on all subsequent proxy,
        // this would eventually trigger all lazy loading,
        // which is NOT what we would expect...
        // TODO : must think of this.
        $this->initializeProxy();

        return parent::toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        // If this is called on all subsequent proxy,
        // this would eventually trigger all lazy loading,
        // which is NOT what we would expect...
        // TODO : must think of this.
        $this->initializeProxy();

        return parent::jsonSerialize();
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($options = 0)
    {
        // If this is called on all subsequent proxy,
        // this would eventually trigger all lazy loading,
        // which is NOT what we would expect...
        // TODO : must think of this.
        $this->initializeProxy();

        return parent::toJson($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->initializeProxy();

        return parent::getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
    {
        $this->initializeProxy();

        return parent::getCachingIterator($flags);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->relationshipLoaded
            ? parent::count()
            : $this->countUsingDatabaseQuery();
    }

    /**
     * Do a count query and return the result.
     *
     * @return int
     */
    protected function countUsingDatabaseQuery() : int
    {
        return $this->getRelationshipInstance()->count();
    }

    /**
     * Get a base Support collection instance from this collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toBase()
    {
        $this->initializeProxy();

        return parent::toBase();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        // TODO rely on QB if no collection
        // initialized
        $this->initializeProxy();

        return parent::offsetExists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        // TODO rely on partial init if no collection
        // initialized
        $this->initializeProxy();

        return parent::offsetGet($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        // TODO : think of the use of it into a ProxyCollection
        // context
        $this->initializeProxy();

        return parent::offsetSet($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        // TODO : think of the use of it into a ProxyCollection
        // context
        $this->initializeProxy();

        return parent::offsetUnset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($key)
    {
        parent::__get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $parameters)
    {
        $this->initializeProxy();

        return parent::__call($method, $parameters);
    }
}
