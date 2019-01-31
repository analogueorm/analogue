<?php

namespace Analogue\ORM;

/**
 * @deprecated 5.5 : use EntityMap and embedded relationships.
 */
class ValueMap
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $embeddables = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $properties = [];

    protected $arrayName = null;

    /**
     * Set this property to true if you wish to use camel case
     * properties.
     *
     * @var bool
     */
    protected $camelCaseHydratation = false;

    /**
     * The mappings to hydrated/dehydrated properties.
     *
     * @var arrat
     */
    protected $mappings = [];

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * [getAttributesArrayName description].
     *
     * @return [type] [description]
     */
    public function getAttributesArrayName()
    {
        return $this->arrayName;
    }

    public function usesAttributesArray()
    {
        return $this->arrayName != null;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getEmbeddables()
    {
        return $this->embeddables;
    }

    /**
     * @param $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (isset($this->name)) {
            return $this->name;
        } else {
            return class_basename($this);
        }
    }

    /**
     * Maps the names of the column names to the appropriate attributes
     * of an entity if the $attributes property of an EntityMap is an
     * associative array.
     *
     * @param array $array
     *
     * @return array
     */
    public function getAttributeNamesFromColumns($array)
    {
        if (!empty($this->mappings)) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $attributeName = isset($this->mappings[$key]) ? $this->mappings[$key] : $key;
                $newArray[$attributeName] = $value;
            }

            return $newArray;
        }

        return $array;
    }

    /**
     * Gets the entity attribute name of a given column in a table.
     *
     * @param string $columnName
     *
     * @return string
     */
    public function getAttributeNameForColumn($columnName)
    {
        if (!empty($this->mappings)) {
            if (isset($this->mappings[$columnName])) {
                return $this->mappings[$columnName];
            }
        }

        return $columnName;
    }

    /**
     * Gets the column name of a given entity attribute.
     *
     * @param string $attributeName
     *
     * @return string
     */
    public function getColumnNameForAttribute($attributeName)
    {
        if (!empty($this->mappings)) {
            $flipped = array_flip($this->mappings);
            if (isset($flipped[$attributeName])) {
                return $flipped[$attributeName];
            }
        }

        return $attributeName;
    }

    /**
     * Maps the attribute names of an entity to the appropriate
     * column names in the database if the $attributes property of
     * an EntityMap is an associative array.
     *
     * @param array $array
     *
     * @return array
     */
    public function getColumnNamesFromAttributes($array)
    {
        if (!empty($this->mappings)) {
            $newArray = [];
            $flipped = array_flip($this->mappings);
            foreach ($array as $key => $value) {
                $attributeName = isset($flipped[$key]) ? $flipped[$key] : $key;
                $newArray[$attributeName] = $value;
            }

            return $newArray;
        }

        return $array;
    }

    public function hasAttribute($attribute)
    {
        if (!empty($this->mappings)) {
            return in_array($attribute, array_values($this->mappings));
        }

        return in_array($attribute, $attributes);
    }
}
