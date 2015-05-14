<?php namespace Analogue\ORM\Plugins\Timestamps;

use Carbon\Carbon;
use Analogue\ORM\Entity;
use Analogue\ORM\EntityMap;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Plugins\AnaloguePlugin;

/**
 * Implements the Timestamps support on Analogue Entities
 */
class TimestampsPlugin extends AnaloguePlugin {

	/**
	 * Register the plugin
	 * 
	 * @return void
	 */
	public function register()
	{
		$this->manager->registerGlobalEvent('initialized', function ($mapper)
		{
			$entityMap = $mapper->getEntityMap();

            if($entityMap->usesTimestamps() )
			{
				$mapper->registerEvent('creating', function($entity) use($entityMap) {
					
					$createdAtField = $entityMap->getCreatedAtColumn();
					
					$updatedAtField = $entityMap->getUpdatedAtColumn();

					$time= new Carbon;

					$entity->setEntityAttribute($createdAtField, $time);
					$entity->setEntityAttribute($updatedAtField, $time);

				});

				$mapper->registerEvent('updating', function($entity) use($entityMap) {

					$updatedAtField = $entityMap->getUpdatedAtColumn();

					$time= new Carbon;

					$entity->setEntityAttribute($updatedAtField,$time);
				});				
			}
		});

	}

  	/**
     * Get custom events provided by the plugin
     *
     * @return array
     */
    public function getCustomEvents()
    {
    	return [];
    }
}
