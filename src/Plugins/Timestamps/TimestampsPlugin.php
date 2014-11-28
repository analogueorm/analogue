<?php namespace Analogue\ORM\Plugins\Timestamps;

use Carbon\Carbon;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityMap;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Plugins\AnaloguePluginInterface;

/**
 * Implements the Eloquent Timestamps behaviour on Analogue Entities
 * 
 */
class TimestampsPlugin implements AnaloguePluginInterface {

	protected $entityMap;

	public function register()
	{
		Manager::registerGlobalEvent('initialized', function ($mapper)
		{
			$entityMap = $mapper->getEntityMap();

			if($entityMap->usesTimestamps() )
			{
				$mapper->registerEvent('creating', function($entity) use($entityMap) {
					
					$createdAtField = $entityMap->getCreatedAtColumn();
					
					$updatedAtField = $entityMap->getUpdatedAtColumn();

					$time= new Carbon;

					$entity->$createdAtField = $time;
					$entity->$updatedAtField = $time;

				});

				$mapper->registerEvent('updating', function($entity) use($entityMap) {

					$updatedAtField = $entityMap->getUpdatedAtColumn();

					$time= new Carbon;

					$entity->$updatedAtField = $time;
				});				
			}
		});

	}

}