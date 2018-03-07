<?php

namespace Analogue\ORM\System\Wrappers;

use Analogue\ORM\EntityMap;
use Analogue\ORM\Exceptions\MappingException;
use Analogue\ORM\System\InternallyMappable;
use Analogue\ORM\System\Proxies\ProxyFactory;
use Zend\Hydrator\HydratorInterface;

/**
 * Mixed wrapper using HydratorGenerator.
 */
class ObjectWrapper implements InternallyMappable
{
    /**
     * Original Entity Object.
     *
     * @var mixed
     */
    protected $entity;

    /**
     * Corresponding EntityMap.
     *
     * @var \Analogue\ORM\EntityMap
     */
    protected $entityMap;

    /**
     * @var \Analogue\ORM\System\Proxies\ProxyFactory
     */
    protected $proxyFactory;

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
     * Set to true if the object's attributes have been modified since last
     * hydration.
     *
     * @var bool
     */
    protected $touched = false;

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
        $this->entity = $entity;
        $this->entityMap = $entityMap;
        $this->proxyFactory = new ProxyFactory();
        $this->attributes = $this->dehydrate($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): string
    {
        return get_class($this->entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityKeyName(): string
    {
        return $this->entityMap->getKeyName();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityKeyValue()
    {
        return $this->getEntityAttribute($this->entityMap->getKeyName());
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityHash(): string
    {
        return $this->getEntityClass().'.'.$this->getEntityKeyValue();
    }

    /**
     * Returns the wrapped entity's map.
     *
     * @return mixed
     */
    public function getMap()
    {
        return $this->entityMap;
    }

    /**
     * Get hydrated object.
     *
     * @return mixed
     */
    public function unwrap()
    {
        if ($this->touched) {
            $this->hydrate();
        }

        return $this->entity;
    }

    /**
     * Returns the wrapped entity.
     *
     * @return mixed
     */
    public function getObject()
    {
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

        $this->touched = false;
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
        $this->touched = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;

        $this->touched = true;

        $this->hydrate();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttribute(string $key)
    {
        if ($this->hasAttribute($key)) {
            return $this->attributes[$key];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Set the lazy loading proxies on the wrapped entity objet.
     *
     * @param array $relations list of relations to be lazy loaded
     *
     * @return void
     */
    public function setProxies(array $relations = null)
    {
        $attributes = $this->getEntityAttributes();
        $proxies = [];

        $relations = $this->getRelationsToProxy();

        // Before calling the relationship methods, we'll set the relationship
        // method to null, to avoid hydration error on class properties
        foreach ($relations as $relation) {
            $this->setEntityAttribute($relation, null);
        }

        foreach ($relations as $relation) {

            // First, we check that the relation has not been already
            // set, in which case, we'll just pass.
            if (array_key_exists($relation, $attributes) && !is_null($attributes[$relation])) {
                continue;
            }

            // If the key is handled locally and we know it not to be set,
            // we'll set the relationship to null value
            if (!$this->relationNeedsProxy($relation, $attributes)) {
                $proxies[$relation] = $this->entityMap->getEmptyValueForRelationship($relation);
            } else {
                $targetClass = $this->getClassToProxy($relation, $attributes);
                $proxies[$relation] = $this->proxyFactory->make($this->getObject(), $relation, $targetClass);
            }
        }

        foreach ($proxies as $key => $value) {
            $this->setEntityAttribute($key, $value);
        }
    }

    /**
     * Get Target class to proxy for a one to one.
     *
     * @param string $relation
     * @param array  $attributes
     *
     * @return string
     */
    protected function getClassToProxy($relation, array $attributes)
    {
        if ($this->entityMap->isPolymorphic($relation)) {
            $localTypeAttribute = $this->entityMap->getLocalKeys($relation)['type'];

            return $attributes[$localTypeAttribute];
        }

        return $this->entityMap->getTargettedClass($relation);
    }

    /**
     * Determine which relations we have to build proxy for, by parsing
     * attributes and finding methods that aren't set.
     *
     * @return array
     */
    protected function getRelationsToProxy()
    {
        $proxies = [];
        $attributes = $this->getEntityAttributes();

        foreach ($this->entityMap->getNonEmbeddedRelationships() as $relation) {
            //foreach ($this->entityMap->getRelationships() as $relation) {

            if (!array_key_exists($relation, $attributes) || is_null($attributes[$relation])) {
                $proxies[] = $relation;
            }
        }

        return $proxies;
    }

    /**
     * Determine if the relation needs a proxy or not.
     *
     * @param string $relation
     * @param array  $attributes
     *
     * @return bool
     */
    protected function relationNeedsProxy($relation, $attributes)
    {
        if (in_array($relation, $this->entityMap->getRelationshipsWithoutProxy())) {
            return false;
        }

        $localKey = $this->entityMap->getLocalKeys($relation);

        if (is_null($localKey)) {
            return true;
        }

        if (is_array($localKey)) {
            $localKey = $localKey['id'];
        }

        if (!isset($attributes[$localKey]) || is_null($attributes[$localKey])) {
            return false;
        }

        return true;
    }
}
