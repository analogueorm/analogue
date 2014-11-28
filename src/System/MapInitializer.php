<?php namespace Analogue\ORM\System;

use Analogue\ORM\EntityMap;
use Analogue\ORM\Relationships\Relationship;

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

		$relationships = $this->guessRelations($map);
		
		$map->setRelationships($relationships);

		return $map;
	}

	/**
	 * Parse entity map relations 
	 * 
	 * @param  EntityMap $map 
	 * @return array
	 */
	public function guessRelations(EntityMap $map)
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