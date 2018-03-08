<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\System\Manager;
use Analogue\ORM\System\ResultBuilder;
use Analogue\ORM\System\Wrappers\Factory;

abstract class EmbeddedRelationship
{
    /**
     * The class that embeds the current relation.
     *
     * @var string
     */
    protected $parentClass;

    /**
     * The class of the embedded relation.
     *
     * @var string
     */
    protected $relatedClass;

    /**
     * The relation attribute on the parent object.
     *
     * @var string
     */
    protected $relation;

    /**
     * If set to true, embedded Object's attributes will
     * be stored as a serialized array in a JSON Column.
     *
     * @var bool
     */
    protected $asArray = false;

    /**
     * If set to true, embedded Object's attributes will
     * be json encoded before storing to database.
     *
     * @var bool
     */
    protected $asJson = false;

    /**
     * Prefix on which the object's attributes are saved into
     * the parent's table. defaults to "<relatedClass>_".
     *
     * @var string
     */
    protected $prefix;

    /**
     * Attributes Map allow the calling EntityMap to overrides attributes
     * on the embedded relation.
     *
     * @var array
     */
    protected $columnMap = [];

    /**
     * Wrapper factory.
     *
     * @var \Analogue\ORM\System\Wrappers\Factory
     */
    protected $factory;

    public function __construct($parent, string $relatedClass, string $relation)
    {
        $this->parentClass = get_class($parent);
        $this->relatedClass = $relatedClass;
        $this->relation = $relation;
        $this->prefix = $relation.'_';
        $this->factory = new Factory();
    }

    /**
     * Switch the 'store as array' feature.
     *
     * @param bool $storeAsArray
     *
     * @return static
     */
    public function asArray(bool $storeAsArray = true)
    {
        $this->asArray = $storeAsArray;

        return $this;
    }

    /**
     * Switch the 'store as json' feature.
     *
     * @param bool $storeAsJson
     *
     * @return static
     */
    public function asJson(bool $storeAsJson = true)
    {
        $this->asJson = $storeAsJson;

        return $this->asArray();
    }

    /**
     * Set the column map for the embedded relation.
     *
     * @param array $columns
     *
     * @return static
     */
    public function setColumnMap(array $columns)
    {
        $this->columnMap = $columns;

        return $this;
    }

    /**
     * Set parent's attribute prefix.
     *
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Return parent's attribute prefix.
     *
     * @return string
     */
    public function getPrefix() : string
    {
        return $this->prefix;
    }

    /**
     * Get the embedded object's attributes that will be
     * hydrated using parent's entity attributes.
     *
     * @return array
     */
    protected function getEmbeddedObjectAttributes() : array
    {
        $entityMap = $this->getRelatedMapper()->getEntityMap();

        $attributes = $entityMap->getAttributes();
        $properties = $entityMap->getProperties();

        return array_merge($attributes, $properties);
    }

    /**
     * Get the corresponding attribute on parent's attributes.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getParentAttributeKey($key) : string
    {
        return $this->getPrefixedAttributeKey($this->getMappedParentAttribute($key));
    }

    /**
     * Get attribute name from the parent, if a map has been
     * defined.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getMappedParentAttribute(string $key) : string
    {
        if (array_key_exists($key, $this->columnMap)) {
            return $this->columnMap[$key];
        } else {
            return $key;
        }
    }

    /**
     * Return the name of the attribute with key.
     *
     * @param string $attributeKey
     *
     * @return string
     */
    protected function getPrefixedAttributeKey(string $attributeKey) : string
    {
        return $this->prefix.$attributeKey;
    }

    /**
     * Transform attributes into embedded object(s), and
     * match it into the given resultset.
     *
     * @return array
     */
    abstract public function match(array $results) : array;

    /**
     * Build an embedded object instance.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    protected function buildEmbeddedObject(array $attributes)
    {
        $resultBuilder = new ResultBuilder($this->getRelatedMapper());

        // TODO : find a way to support eager load within an embedded
        // object.
        $eagerLoads = [];

        return $resultBuilder->build([$attributes], $eagerLoads)[0];
    }

    /**
     * Transform embedded object into db column(s).
     *
     * @param mixed $object
     *
     * @return array $columns
     */
    abstract public function normalize($object) : array;

    /**
     * Return parent mapper.
     *
     * @return \Analogue\ORM\System\Mapper
     */
    protected function getParentMapper()
    {
        return Manager::getInstance()->mapper($this->parentClass);
    }

    /**
     * Return embedded relationship mapper.
     *
     * @return \Analogue\ORM\System\Mapper
     */
    protected function getRelatedMapper()
    {
        return Manager::getInstance()->mapper($this->relatedClass);
    }
}
