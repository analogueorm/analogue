<?php

namespace Analogue\ORM\Relationships;

use Analogue\ORM\System\Manager;
use Analogue\ORM\System\ResultBuilder;
use Analogue\ORM\System\Wrappers\Factory;
use Analogue\ORM\System\Wrappers\Wrapper;

abstract class EmbeddedRelationship
{
	/**
	 * The class that embeds the current relation
	 * 
	 * @var string
	 */
	protected $parentClass;

	/**
	 * The class of the embedded relation
	 * 
	 * @var string
	 */
	protected $relatedClass;

	/**
	 * The relation attribute on the parent object
	 * 
	 * @var string
	 */
	protected $relation;

	/**
	 * If set to true, embedded Object's attributes will
	 * be stored as a serialized array in a JSON Column 
	 * 
	 * @var boolean
	 */
	protected $asArray = false;

	/**
	 * Prefix on which the object's attributes are saved into
	 * the parent's table. defaults to "<relatedClass>_"
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

	public function __construct(string $parentClass, string $relatedClass, string $relation)
	{
		$this->parentClass = $parentClass;
		$this->relatedClass = $relatedClass;
		$this->relation = $relation;
		$this->setDefaultPrefix();
		$this->factory = new Factory;
	}

	/**
	 * Switch the 'store as array' feature
	 * 
	 * @param  bool  $storeAsArray
	 * @return static
	 */
	public function asArray(bool $storeAsArray = true)
	{
		$this->asArray = $storeAsArray;
		return $this;
	}

	/**
	 * Set the column map for the embedded relation
	 * 
	 * @param array $columns 
	 * @return  static
	 */
	public function setColumnMap(array $columns)
	{
		$this->columnMap = $columns;
		return $this;
	}	

	/**
	 * Set the default prefix for the embedded attributes
	 */
	protected function setDefaultPrefix()
	{
		$this->prefix = class_basename($this->relatedClass)."_";
	}

	/**
	 * Set parent's attribute prefix
	 *
	 * @param string $prefix
	 * @return  static
	 */
	public function setPrefix(string $prefix)
	{
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * Return parent's attribute prefix
	 * 
	 * @return string
	 */
	public function getPrefix() : string
	{
		return $this->prefix;
	}

	/**
	 * Transform attributes into embedded object(s), and
	 * match it into the given resultset
	 * 
	 * @return array
	 */
	abstract public function match(array $results) : array;

	/**
	 * Build an embedded object instance
	 * 
	 * @param  array  $attributes
	 * @return mixed
	 */
	protected function buildEmbeddedObject(array $attributes)
	{
		$resultBuilder = new ResultBuilder($this->getRelatedMapper());

		// TODO : find a way to support eager load within an embedded
		// object. 	
		$eagerLoads = [];
	}


	/**
	 * Transform embedded object into db column(s)
	 * 
	 * @param  mixed $object 
	 * @return array $columns
	 */
	abstract public function normalize($object) : array;

	/**
	 * Return parent mapper
	 * 
	 * @return Analogue\ORM\System\Mapper
	 */
	protected function getParentMapper()
	{
		return Manager::getInstance()->mapper($this->parentClass);
	}

	/**
	 * Return embedded relationship mapper
	 * 
	 * @return Analogue\ORM\System\Mapper
	 */
	protected function getRelatedMapper()
	{
		return Manager::getInstance()->mapper($this->parentClass);
	}

	/**
	 * Return Object wrapper for related entity
	 * 
	 * @return Wrapper
	 */
	protected function getWrapper($instance = null) : Wrapper
	{
		if(is_null($instance)) {
			$instance = $this->getRelatedMapper()->newInstance();
		}

		return $this->factory->make($instance);
	}
}