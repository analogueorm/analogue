<?php

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\Exceptions\MappingException;
use Zend\Hydrator\HydratorInterface;

/**
 * Mixed wrapper using HydratorGenerator.
 */
class ObjectWrapper extends Wrapper
{
    /**
     * Internal Representation of analogue's entity attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * All properties on the original object.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Object properties that are not a part of the entity attributes,
     * but which are needed to correctly hydrate the Object.
     *
     * @var array
     */
    protected $unmanagedProperties = [];

    /**
     * The hydrator for the wrapped object.
     *
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * Object Wrapper constructor.
     *
     * @param mixed                   $entity
     * @param \Analogue\ORM\EntityMap $entityMap
     * @param HydratorInterface       $hydrator
     */
    public function __construct($entity, $entityMap, HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;

        parent::__construct($entity, $entityMap);

        $this->attributes = $this->dehydrate($entity);
    }

    /**
     * Get hydrated object.
     *
     * @return mixed
     */
    public function unwrap()
    {
        $this->hydrate();

        return $this->entity;
    }

    /**
     * Returns the wrapped entity.
     *
     * @return mixed
     */
    public function getObject()
    {
        //$this->hydrate();

        return $this->entity;
    }

    /**
     * Extract entity attributes / properties to an array of attributes.
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function dehydrate($entity): array
    {
        $properties = $this->hydrator->extract($entity);

        $this->properties = $properties;

        $this->unmanagedProperties = array_except($properties, $this->getManagedProperties());

        return $this->attributesFromProperties($properties);
    }

    /**
     * Hydrate object's properties/attribute from the internal array representation.
     *
     * @return void
     */
    protected function hydrate()
    {
        $properties = $this->propertiesFromAttributes() + $this->unmanagedProperties;

        // In some case, attributes will miss some properties, so we'll just complete the hydration
        // set with the original object properties
        $missingProperties = array_diff_key($this->properties, $properties);

        foreach (array_keys($missingProperties) as $missingKey) {
            $properties[$missingKey] = $this->properties[$missingKey];
        }

        $this->hydrator->hydrate($properties, $this->entity);
    }

    /**
     * Return properties that will be extracted from the entity.
     *
     * @return array
     */
    protected function getManagedProperties(): array
    {
        $properties = $this->entityMap->getProperties();

        $attributesName = $this->entityMap->getAttributesArrayName();

        return $attributesName == null ? $properties : array_merge($properties, [$attributesName]);
    }

    /**
     * Convert object's properties to analogue's internal attributes representation.
     *
     * @param array $properties
     *
     * @throws MappingException
     *
     * @return array
     */
    protected function attributesFromProperties(array $properties): array
    {
        // First, we'll only keep the entities that are part of the Entity's
        // attributes
        $managedProperties = $this->getManagedProperties();

        $properties = array_only($properties, $managedProperties);

        // If the entity does not uses the attributes array to store
        // part of its attributes, we'll directly return the properties
        if (!$this->entityMap->usesAttributesArray()) {
            return $properties;
        }

        $arrayName = $this->entityMap->getAttributesArrayName();

        if (!array_key_exists($arrayName, $properties)) {
            throw new MappingException("Property $arrayName not set on object of type ".$this->getEntityClass());
        }

        if (!is_array($properties[$arrayName])) {
            throw new MappingException("Property $arrayName should be an array.");
        }

        $attributes = $properties[$arrayName];

        unset($properties[$arrayName]);

        return $properties + $attributes;
    }

    /**
     * Convert internal representation of attributes to an array of properties
     * that can hydrate the actual object.
     *
     * @return array
     */
    protected function propertiesFromAttributes(): array
    {
        // Get all managed properties
        $propertyNames = $this->entityMap->getProperties();

        $propertyAttributes = array_only($this->attributes, $propertyNames);
        $attributesArray = array_except($this->attributes, $propertyNames);

        $attributesArrayName = $this->entityMap->getAttributesArrayName();

        if ($attributesArrayName) {
            $propertyAttributes[$attributesArrayName] = $attributesArray;
        }

        return $propertyAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        //$this->hydrate();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes(): array
    {
        //$this->attributes = $this->dehydrate($this->entity);

        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityAttribute(string $key, $value)
    {
        //$this->attributes = $this->dehydrate($this->entity);

        $this->attributes[$key] = $value;

        $this->hydrate();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttribute(string $key)
    {
        //$this->attributes = $this->dehydrate($this->entity);

        if ($this->hasAttribute($key)) {
            return $this->attributes[$key];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute(string $key): bool
    {
        //$this->attributes = $this->dehydrate($this->entity);

        return array_key_exists($key, $this->attributes);
    }
}
