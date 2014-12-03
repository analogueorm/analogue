<?php namespace Analogue\ORM;

class ValueMap {

	protected $name;

	protected $class;

	protected $embeddables = [];

	protected $attributes = [];

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function getEmbeddables()
	{
		return $this->embeddables;
	}

	public function setClass($class)
	{
		$this->class = $class;
	}

	public function getClass()
	{
		return $this->class;
	}

	public function getName()
	{
		if (isset($this->name))
		{
			return $this->name;
		}
		else return class_basename($this);
	}

}