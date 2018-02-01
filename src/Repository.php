<?php

namespace Analogue\ORM;

use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Mapper;
use Exception;
use InvalidArgumentException;

/**
 * Class Repository.
 *
 * @mixin Mapper
 *
 * @deprecated : will be removed in v5.6
 */
class Repository
{
    /**
     * The mapper object for the corresponding entity.
     *
     * @var \Analogue\ORM\System\Mapper
     */
    protected $mapper;

    /**
     * To build a repository, either provide :.
     *
     * - Mappable object's class name as a string
     * - Mappable object instance
     * - Instance of mapper
     *
     * @param Mapper|Mappable|string $mapper
     * @param EntityMap|null         $entityMap (optional)
     *
     * @throws \InvalidArgumentException
     * @throws MappingException
     */
    public function __construct($mapper, EntityMap $entityMap = null)
    {
        if ($mapper instanceof Mappable || is_string($mapper)) {
            $this->mapper = Manager::getMapper($mapper, $entityMap);
        } elseif ($mapper instanceof Mapper) {
            $this->mapper = $mapper;
        } else {
            new InvalidArgumentException('Repository class constructor need a valid Mapper or Mappable object.');
        }
    }

    /**
     * Return all Entities from database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->mapper->get();
    }

    /**
     * Fetch a record from the database.
     *
     * @param int $id
     *
     * @return \Analogue\ORM\Mappable
     */
    public function find($id)
    {
        return $this->mapper->find($id);
    }

    /**
     * Get the first entity matching the given attributes.
     *
     * @param array $attributes
     *
     * @return \Analogue\ORM\Mappable|null
     */
    public function firstMatching(array $attributes)
    {
        return $this->mapper->where($attributes)->first();
    }

    /**
     * Return all the entities matching the given attributes.
     *
     * @param array $attributes
     *
     * @return \Analogue\ORM\EntityCollection
     */
    public function allMatching(array $attributes)
    {
        return $this->mapper->where($attributes)->get();
    }

    /**
     * Return a paginator instance on the EntityCollection.
     *
     * @param int|null $perPage number of item per page (fallback on default setup in entity map)
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null)
    {
        return $this->mapper->paginate($perPage);
    }

    /**
     * Delete an entity or an entity collection from the database.
     *
     * @param Mappable|EntityCollection $entity
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Support\Collection|null
     */
    public function delete($entity)
    {
        return $this->mapper->delete($entity);
    }

    /**
     * Persist an entity or an entity collection in the database.
     *
     * @param Mappable|EntityCollection|array $entity
     *
     * @throws MappingException
     * @throws \InvalidArgumentException
     *
     * @return Mappable|EntityCollection|array
     */
    public function store($entity)
    {
        return $this->mapper->store($entity);
    }

    /**
     * Make custom mapper custom commands available in repository.
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
        if ($this->mapper->hasCustomCommand($method)) {
            call_user_func_array([$this->mapper, $method], $parameters);
        } else {
            throw new Exception("No method $method on ".get_class($this));
        }
    }
}
