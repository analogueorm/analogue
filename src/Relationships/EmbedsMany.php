<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\Exceptions\MappingException;
use Illuminate\Support\Collection;

class EmbedsMany extends EmbedsOne
{
    /**
     * Match a single database row's attributes to a single
     * object, and return the updated attributes.
     *
     * @param array $attributes
     *
     * @throws MappingException
     *
     * @return array
     */
    public function matchSingleResult(array $attributes) : array
    {
        $column = $this->relation;

        if (!$this->asArray) {
            throw new MappingException("column '$column' should be of type array or json");
        }

        return $this->matchAsArray($attributes);
    }

    /**
     * Match array attribute from parent to an embedded object,
     * and return the updated attributes.
     *
     * @param array $attributes
     *
     * @throws MappingException
     *
     * @return array
     */
    protected function matchAsArray(array $attributes) : array
    {
        // Extract the attributes with the key of the relation,
        // which should be an array.
        $key = $this->relation;

        if (!array_key_exists($key, $attributes)) {
            $attributes[$key] = $this->asJson ? json_encode([]) : [];
        }

        if ($this->asJson) {
            $attributes[$key] = json_decode($attributes[$key], true);
        }

        if (!is_array($attributes[$key])) {
            throw new MappingException("'$key' column should be an array, actual :".$attributes[$key]);
        }

        $attributes[$key] = $this->buildEmbeddedCollection($attributes[$key]);

        return $attributes;
    }

    /**
     * Build an embedded collection and returns it.
     *
     * @param array $rows
     *
     * @return Collection
     */
    protected function buildEmbeddedCollection(array $rows): Collection
    {
        return collect($rows)->map(function ($attributes) {
            return $this->buildEmbeddedObject($attributes);
        });
    }

    /**
     * Transform embedded object into db column(s).
     *
     * @param mixed $objects
     *
     * @throws MappingException
     *
     * @return array $columns
     */
    public function normalize($objects) : array
    {
        if (!$this->asArray) {
            throw new MappingException('Cannot normalize an embedsMany relation as row columns');
        }

        return $this->normalizeAsArray($objects);
    }

    /**
     * Normalize object as array containing raw attributes.
     *
     * @param mixed $objects
     *
     * @throws MappingException
     *
     * @return array
     */
    protected function normalizeAsArray($objects) : array
    {
        $key = $this->relation;

        if (!is_array($objects) && !$objects instanceof Collection) {
            throw new MappingException("column '$key' should be of type array or collection");
        }

        if ($objects instanceof Collection) {
            $objects = $objects->all();
        }

        $normalizedObjects = [];

        foreach ($objects as $object) {
            $wrapper = $this->factory->make($object);
            $normalizedObjects[] = $wrapper->getEntityAttributes();
        }

        if ($this->asJson) {
            $normalizedObjects = json_encode($normalizedObjects);
        }

        return [$key => $normalizedObjects];
    }
}
