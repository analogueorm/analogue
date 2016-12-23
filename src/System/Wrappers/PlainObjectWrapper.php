<?php

namespace Analogue\ORM\System\Wrappers;

use ReflectionClass;

class PlainObjectWrapper extends Wrapper
{
    /**
     * The list of attributes for the managed entity
     *
     * @var array
     */
    protected $attributeList;

    /**
     * The reflection class for the managed entity
     *
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * PlainObjectWrapper constructor.
     * @param $popoEntity
     * @param $entityMap
     */
    public function __construct($popoEntity, $entityMap)
    {
        $this->reflection = new ReflectionClass($popoEntity);

        parent::__construct($popoEntity, $entityMap);

        $this->attributeList = $this->getAttributeList();
    }

    /**
     * Get Compiled Attributes (key, attributes, embed, relations)
     *
     * @return array
     */
    protected function getAttributeList()
    {
        return  $this->entityMap->getCompiledAttributes();
    }

    /**
     * Extract Attributes from a Plain Php Object
     *
     * @return array $attributes
     */
    protected function extract()
    {
        $properties = $this->getMappedProperties();

        $attributes = [];

        foreach ($properties as $property) {
            $name = $property->getName();

            if ($property->isPublic()) {
                $attributes[$name] = $this->entity->$name;
            } else {
                $property->setAccessible(true);
    
                $attributes[$name] = $property->getValue($this->entity);
            }
        }

        return $attributes;
    }

    /**
     * @return \ReflectionProperty[]
     */
    protected function getMappedProperties()
    {
        $objectProperties = $this->reflection->getProperties();

        $attributeList = $this->getAttributeList();

        // We need to filter out properties that could belong to the object
        // and which are not intended to be handled by the ORM
        return array_filter($objectProperties, function (\ReflectionProperty $item) use ($attributeList) {
            if (in_array($item->getName(), $attributeList)) {
                return true;
            }
        });
    }

    /**
     * @param  string $name
     * @return \ReflectionProperty
     */
    protected function getMappedProperty($name)
    {
        $name = $this->entityMap->mapColumnToAttribute($name);
        return $this->reflection->getProperty($name);
    }

    /**
     * Hydrate Plain PHP Object with wrapped attributes
     *
     * @param  $attributes
     * @return void
     */
    protected function hydrate($attributes)
    {
        $properties = $this->getMappedProperties();

        foreach ($properties as $property) {
            $name = $property->getName();

            if ($property->isPublic()) {
                $this->entity->$name = $attributes[$name];
            } else {
                $property->setAccessible(true);
                if (isset($attributes[$name])) {
                    $property->setValue($this->entity, $attributes[$name]);
                }
            }
        }
    }

    /**
     * Method used by the mapper to set the object
     * attribute raw values (hydration)
     *
     * @param array $attributes
     *
     * @return void
     */
    public function setEntityAttributes(array $attributes)
    {
        $this->hydrate($attributes);
    }

    /**
     * Method used by the mapper to get the
     * raw object's values.
     *
     * @return array
     */
    public function getEntityAttributes()
    {
        return $this->extract();
    }

    /**
     * Method used by the mapper to set raw
     * key-value pair
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setEntityAttribute($key, $value)
    {
        $property = $this->getMappedProperty($key);

        if ($property->isPublic()) {
            $this->entity->$key = $value;
        } else {
            $property->setAccessible(true);
    
            $property->setValue($this->entity, $value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Method used by the mapper to get single
     * key-value pair
     *
     * @param  string $key
     * @return mixed
     */
    public function getEntityAttribute($key)
    {
        $property = $this->getMappedProperty($key);

        if ($property->isPublic()) {
            $value = $this->entity->$key;
        } else {
            $property->setAccessible(true);
            $value = $property->getValue($this->entity);
        }

        return $value;
    }

        /**
         * Test if a given attribute exists
         *
         * @param  string  $key
         * @return boolean
         */
    public function hasAttribute($key)
    {
        if (array_key_exists($key, $this->attributeList)) {
            return true;
        } else {
            return false;
        }
    }
}
