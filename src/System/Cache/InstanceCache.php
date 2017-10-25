<?php

namespace Analogue\ORM\System\Cache;

use Analogue\ORM\Exceptions\CacheException;

class InstanceCache
{
    /**
     * Class name of cached objects.
     *
     * @var string
     */
    protected $class;

    /**
     * Intances.
     *
     * @var array
     */
    protected $instances = [];

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * Add an entity to the cache.
     *
     * @param mixed  $entity
     * @param string $id
     *
     * @throws CacheException
     *
     * @return void
     */
    public function add($entity, $id)
    {
        $entityClass = get_class($entity);

        if ($entityClass !== $this->class) {
            throw new CacheException('Tried to cache an instance with a wrong type : expected '.$this->class.", got $entityClass");
        }

        if ($this->has($id)) {
            throw new CacheException("Tried to cache an instance which is already cached. Id : $id");
        }

        $this->instances[$id] = $entity;
    }

    /**
     * Check if an instance exists in the cache.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has($id) : bool
    {
        return array_key_exists($id, $this->instances);
    }

    /**
     * Return an entity's instance.
     *
     * @param string $id
     *
     * @throws CacheException
     *
     * @return mixed|null
     */
    public function get($id)
    {
        if ($id === null) {
            throw new CacheException('Cached instance id cannot be null');
        }

        if (!$this->has($id)) {
            return;
        }

        return $this->instances[$id];
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clear()
    {
        $this->instances = [];
    }
}
