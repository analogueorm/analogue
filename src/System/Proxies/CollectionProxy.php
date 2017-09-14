<?php

namespace Analogue\ORM\System\Proxies;

use Analogue\ORM\EntityCollection;
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

    protected $addedItems = [];

    /**
     * Create a new collection.
     *
     * @param mixed  $entity
     * @param string $relation
     *
     * @return void
     */
    public function __construct($entity, $relation)
    {
        $this->parentEntity = $entity;
        $this->relationshipMethod = $relation;
        parent::__construct();
    }

    /**
     * Return Items that has been added without lady loading
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

        $relation = $this->relationshipMethod;
        $entity = $this->parentEntity;

        $entityMap = Manager::getMapper($entity)->getEntityMap();

        $this->items = $entityMap->$relation($entity)->getResults($relation)->all() + $this->addedItems;

        $this->relationshipLoaded = true;

        return true;
    }

    /**
     * Retrieves current initialization status of the proxy.
     *
     * @return bool
     */
    public function isProxyInitialized() : bool
    {
        return $this->relationshipLoaded;
    }

    /**
     * Return a base Collection with current items.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function toBaseCollection() : Collection
    {
        return new Collection($this->items);
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        $this->initializeProxy();

        return parent::all();
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function avg($callback = null)
    {
        $this->initializeProxy();

        return parent::avg($callback);
    }

    /**
     * Get the median of a given key.
     *
     * @param null $key
     *
     * @return mixed|null
     */
    public function median($key = null)
    {
        $this->initializeProxy();

        return parent::median($key);
    }

    /**
     * Get the mode of a given key.
     *
     * @param mixed $key
     *
     * @return array
     */
    public function mode($key = null)
    {
        $this->initializeProxy();

        return parent::mode($key);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->collapse();
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        $this->initializeProxy();

        return parent::contains($key, $operator, $value);
    }

    /**
     * Determine if an item exists in the collection using strict comparison.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        $this->initializeProxy();

        return parent::containsStrict($key, $value);
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diff($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diff($items);
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffKeys($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffKeys($items);
    }

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        $this->initializeProxy();

        return parent::each($callback);
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     *
     * @return static
     */
    public function every($key, $operator = null, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->every($key, $operator, $value);
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function except($keys)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->except($keys);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function filter(callable $callback = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->filter($callback);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function where($key, $operator, $value = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->where($key, $operator, $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereNotIn($key, $values, $strict);
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     */
    public function whereStrict($key, $value)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereStrict($key, $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereIn($key, $values, $strict);
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $values
     *
     * @return static
     */
    public function whereInStrict($key, $values)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->whereInStrict($key, $values);
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        // TODO Consider partial loading
        $this->initializeProxy();

        return parent::first($callback, $default);
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param int $depth
     *
     * @return static
     */
    public function flatten($depth = INF)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->flatten($depth);
    }

    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->flip();
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param string|array $keys
     *
     * @return $this
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
     * Get an item from the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // TODO : We could also consider partial loading
        // here
        $this->initializeProxy();

        return parent::get($key, $default);
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param callable|string $groupBy
     * @param bool            $preserveKeys
     *
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->groupBy($groupBy, $preserveKeys);
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param callable|string $keyBy
     *
     * @return static
     */
    public function keyBy($keyBy)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->keyBy($keyBy);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param mixed $key
     *
     * @return bool
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
     * Concatenate values of a given key as a string.
     *
     * @param string $value
     * @param string $glue
     *
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $this->initializeProxy();

        return parent::implode($value, $glue);
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersect($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->intersect($items);
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        $this->initializeProxy();

        return parent::isEmpty();
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->keys();
    }

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        // TODO : we could do partial loading there as well
        $this->initializeProxy();

        return parent::last($callback, $default);
    }

    /**
     * Get the values of a given key.
     *
     * @param string      $value
     * @param string|null $key
     *
     * @return static
     */
    public function pluck($value, $key = null)
    {
        // TODO : automagic call to QB if not initialized
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->pluck($value, $key);
    }

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function map(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->map($callback);
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->mapWithKeys($callback);
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function flatMap(callable $callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->flatMap($callback);
    }

    /**
     * Get the max value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $this->initializeProxy();

        return parent::max($callback);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function merge($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->merge($items);
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param mixed $values
     *
     * @return static
     */
    public function combine($values)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->combine($values);
    }

    /**
     * Create a new collection by invoking the callback a given amount of times.
     *
     * @param int      $amount
     * @param callable $callback
     *
     * @return static
     */
    public static function times($amount, callable $callback = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->times($amount, $callback);
    }

    /**
     * Cross join with the given lists, returning all possible permutations.
     *
     * @param mixed ...$lists
     *
     * @return static
     */
    public function crossJoin(...$lists)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->times(func_num_args());
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diffAssoc($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->diffAssoc($items);
    }

    /**
     * Intersect the collection with the given items by key.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersectKey($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->intersectKey($items);
    }

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function union($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->union($items);
    }

    /**
     * Get the min value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        // TODO : we could rely on the QB
        // for thos, if initialization has not
        // take place yet
        $this->initializeProxy();

        return parent::min($callback);
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     *
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->nth($step, $offset);
    }

    /**
     * Get the items with the specified keys.
     *
     * @param mixed $keys
     *
     * @return static
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
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return static
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
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param callable|string $callback
     *
     * @return static
     */
    public function partition($callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->partition($callback);
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        $this->initializeProxy();

        return parent::pipe($callback);
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        $this->initializeProxy();

        return parent::pop();
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param mixed $value
     * @param mixed $key
     *
     * @return $this
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
     * Push an item onto the end of the collection.
     *
     * @param mixed $value
     *
     * @return $this
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
     * Get and remove an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        // TODO : QB query if the collection
        // hasn't been initialized yet

        $this->initializeProxy();

        return parent::pull($key, $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function put($key, $value)
    {
        // TODO : Partial loading ?

        $this->initializeProxy();

        return parent::put($key, $value);
    }

    /**
     * Get one or more items randomly from the collection.
     *
     * @param int $amount
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function random($amount = 1)
    {
        // TODO : we could optimize this by only
        // fetching the keys from the database
        // and performing partial loading

        $this->initializeProxy();

        return parent::random($amount);
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        $this->initializeProxy();

        return parent::reduce($callback, $initial);
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param callable|mixed $callback
     *
     * @return static
     */
    public function reject($callback)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->reject($callback);
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->reverse();
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool  $strict
     *
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        $this->initializeProxy();

        return parent::search($value, $strict);
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        // Todo : Partial Removing
        // we could have a pending removal array
        $this->initializeProxy();

        return parent::shift();
    }

    /**
     * Shuffle the items in the collection.
     *
     * @param int $seed
     *
     * @return static
     */
    public function shuffle($seed = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->shuffle($seed);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param int $offset
     * @param int $length
     *
     * @return static
     */
    public function slice($offset, $length = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->slice($offset, $length);
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * @param int $numberOfGroups
     *
     * @return static
     */
    public function split($numberOfGroups)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->split($numberOfGroups);
    }

    /**
     * Chunk the underlying collection array.
     *
     * @param int $size
     *
     * @return static
     */
    public function chunk($size)
    {
        // TODO : partial loading ?
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->chunk($size);
    }

    /**
     * Sort through each item with a callback.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->sort($callback);
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param callable|string $callback
     * @param int             $options
     * @param bool            $descending
     *
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->sort($callback, $options, $descending);
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param int      $offset
     * @param int|null $length
     * @param mixed    $replacement
     *
     * @return static
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->splice($offset, $length, $replacement);
    }

    /**
     * Get the sum of the given values.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function sum($callback = null)
    {
        $this->initializeProxy();

        return parent::sum($callback);
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     *
     * @return static
     */
    public function take($limit)
    {
        // TODO: partial loading
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->take($limit);
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function tap(callable $callback)
    {
        $this->initializeProxy();

        return parent::tap($this);
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->initializeProxy();

        return parent::transform($callback);
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param string|callable|null $key
     * @param bool                 $strict
     *
     * @return static
     */
    public function unique($key = null, $strict = false)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->unique($key, $strict);
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values()
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->values();
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * @param bool     $value
     * @param callable $callback
     * @param callable $default
     *
     * @return mixed
     */
    public function when($value, callable $callback, callable $default = null)
    {
        $this->initializeProxy();

        return parent::when($value, $callback);
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param mixed ...$items
     *
     * @return static
     */
    public function zip($items)
    {
        $this->initializeProxy();

        $parent = $this->toBaseCollection();

        return $parent->zip($items);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
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
     * Convert the object into something JSON serializable.
     *
     * @return array
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
     * Get the collection of items as JSON.
     *
     * @param int $options
     *
     * @return string
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
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->initializeProxy();

        return parent::getIterator();
    }

    /**
     * Get a CachingIterator instance.
     *
     * @param int $flags
     *
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
    {
        $this->initializeProxy();

        return parent::getCachingIterator($flags);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        // TODO rely on QB if not initialized
        $this->initializeProxy();

        return parent::count();
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
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        // TODO rely on QB if no collection
        // initialized
        $this->initializeProxy();

        return parent::offsetExists($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        // TODO rely on partial init if no collection
        // initialized
        $this->initializeProxy();

        return parent::offsetGet($key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        // TODO : think of the use of it into a ProxyCollection
        // context
        $this->initializeProxy();

        return parent::offsetSet($key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        // TODO : think of the use of it into a ProxyCollection
        // context
        $this->initializeProxy();

        return parent::offsetUnset($key);
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function __get($key)
    {
        parent::__get($key);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->initializeProxy();

        return parent::__call($method, $parameters);
    }
}
