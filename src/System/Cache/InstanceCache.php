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
     * Instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * InstanceCache constructor.
     *
     * @param string $class
     */
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
    public function add($entity, string $id)
    {
        $entityClass = get_class($entity);

        if ($entityClass !== $this->class) {
            throw new CacheException('Tried to cache an instance with a wrong type : expected '.$this->class.", got $entityClass");
        }

        // Cache once and ignore subsequent caching
        // attempts if the entity is already stored
        if (!$this->has($id)) {
            $this->instances[$id] = $entity;
        }
    }

    /**
     * Check if an instance exists in the cache.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances);
    }

    /**
     * Return an entity's instance.
     *
     * @param string $id
     *
     * @return mixed|null
     */
    public function get(string $id)
    {
        if ($this->has($id)) {
            return $this->instances[$id];
        }
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
