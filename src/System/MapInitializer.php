<?php namespace Analogue\ORM\System;

use Analogue\ORM\EntityMap;
use Analogue\ORM\Relationships\Relationship;
use ReflectionClass;

/**
 * This class register some edm behaviour specified by
 * the map configuration.
 *
 */
class MapInitializer {

	protected $manyClasses = ['BelongsToMany', 'HasMany', 'HasManyThrough', 
		'MorphMany', 'MorphToMany'];


	protected $singleClasses = ['BelongsTo', 'HasOne', 'MorphOne','MorphTo'];

	protected $entityMap;

	public function __construct(EntityMap $entityMap)
	{
		$this->entityMap = $entityMap;
	}

	public function init()
	{
		$map = $this->entityMap;

		$userMethods = $this->getCustomMethods($map);

		if(count($userMethods) > 0)
		{
			$relationships = $this->parseMethodsForRelationship($map, $userMethods);
		}
		else
		{
			$relationships = [];
		}

		$map->setRelationships($relationships);

		return $map;
	}

	public function parseMethodsForRelationship(EntityMap $map, array $guessedRelations)
	{
		$relationships = [];

		$class = new ReflectionClass(get_class($map));

		foreach($guessedRelations as $methodName) {
			$method = $class->getMethod($methodName);

			if($method->getNumberOfParameters() == 0) {
				continue;
			}

			$params = $method->getParameters();

			if ($params[0]->getClass() && $params[0]->getClass()->implementsInterface('Analogue\ORM\Mappable')) {
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
	 * 
	 * @param  Mappable $entity
	 * @return void
	 */
	public function splitRelationsTypes($entity)
	{
		$map = $this->entityMap;
		
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
