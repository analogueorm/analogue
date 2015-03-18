<?php namespace Analogue\ORM\System;

use Analogue\ORM\EntityMap;
use Analogue\ORM\Relationships\Relationship;
use ReflectionClass, ReflectionMethod;

/**
 * This class register some edm behaviour specified by
 * the map configuration.
 *
 */
class MapInitializer {

	protected $manyClasses = ['BelongsToMany', 'HasMany', 'HasManyThrough', 
		'MorphMany', 'MorphToMany'];


	protected $singleClasses = ['BelongsTo', 'HasOne', 'MorphOne','MorphTo'];

	protected $mapper;

	public function __construct($mapper)
	{
		$this->mapper = $mapper;
	}

	public function init()
	{
		$map = $this->mapper->getEntityMap();

		$userMethods = $this->getCustomMethods($map);

		if(count($userMethods) > 0)
		{
			$relationships = $this->parseMethodsForRelationship($map, $userMethods);
		}
		else
		{
			$relationships = [];
		}

		if (in_array('activator',$userMethods))
		{
			$map->setActivatorMethod();
		}

		$map->setRelationships($relationships);

		return $map;
	}

	public function parseMethodsForRelationship(EntityMap $map, array $guessedRelations)
	{
		$relationships = [];

		$class = new ReflectionClass(get_class($map));

		foreach($guessedRelations as $methodName)
		{
			$method = $class->getMethod($methodName);

			if($method->getNumberOfParameters() == 0) continue;

			$params = $method->getParameters();

			if ($params[0]->getClass()->implementsInterface('Analogue\ORM\Mappable'))
			{
				$relationships[] = $methodName;
			}
		}

		return $relationships;
	}

	/**
	 * Guess entity map relations 
	 * 
	 * @param  EntityMap $map 
	 * @return array
	 */
	public function getCustomMethods(EntityMap $map)
	{
		$mapMethods = get_class_methods($map);

		$parentsMethods = get_class_methods('Analogue\ORM\EntityMap');
		
		$guessedRelations = array_diff($mapMethods, $parentsMethods);

		return $guessedRelations;
	}

	/**
	 * Parse the EntityMap and split single vs multi relations
	 * @param  EntityMap $map 
	 * @return void
	 */
	public function splitRelationsTypes($entity)
	{
		$map = $this->mapper->getEntityMap();
		// Add dynamic relationships to the game
		$relations = $map->getRelationships() + $map->getDynamicRelationships();

		$singleRelations = [];

		$manyRelations = [];

		foreach($relations as $relation)
		{
			$relationObject = $map->$relation($entity);

			$class = class_basename(get_class($relationObject));

			if (in_array($class, $this->singleClasses)) 
			{
				$singleRelations[] = $relation;
			}

			if (in_array($class, $this->manyClasses)) 
			{
				$manyRelations[] = $relation;
			}

		}
		
		$map->setSingleRelationships($singleRelations);

		$map->setManyRelationships($manyRelations);
	}
}