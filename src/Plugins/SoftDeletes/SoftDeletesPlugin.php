<?php namespace Analogue\ORM\Plugins\SoftDeletes;

use Carbon\Carbon;
use Analogue\ORM\System\Manager;
use Analogue\ORM\System\Mapper;
use Analogue\ORM\Plugins\AnaloguePlugin;

class SoftDeletesPlugin extends AnaloguePlugin {

	/**
	 * Register the plugin
	 * 
	 * @return void
	 */
	public function register()
	{
		$host = $this;

		// Hook any mapper init and check the mapping include soft deletes.
		$this->manager->registerGlobalEvent('initialized', function ($mapper) use ($host)
		{
			$entityMap = $mapper->getEntityMap();

			if($entityMap->usesSoftDeletes())
			{
				$host->registerSoftDelete($mapper);
			}

		});
	}

	/**
	 * By hooking to the mapper initialization event, we can extend it
	 * with the softDelete capacity.
	 * 
	 * @param  \Analogue\ORM\System\Mapper 	$mapper 
	 * @return void
	 */
	protected function registerSoftDelete(Mapper $mapper)
	{
		$entityMap = $mapper->getEntityMap();

		// Add Scopes
		$mapper->addGlobalScope(new SoftDeletingScope);

		$host = $this;

		// Register 'deleting' events
		$mapper->registerEvent('deleting', function($entity) use($entityMap, $host) {
					
			$deletedAtField = $entityMap->getQualifiedDeletedAtColumn();
			
			if(! is_null($entity->getEntityAttribute($deletedAtField)))
			{
				return true;
			}
			else
			{
				$time= new Carbon;
				$entity->$deletedAtField = $time;

				// Launch an update instead
				$host->manager->mapper(get_class($entity))->store($entity);

				return false;
			}

		});

		// Register RestoreCommand
		$mapper->addCustomCommand('Analogue\ORM\Plugins\SoftDeletes\Restore');
		
		
	}

	/**
     * Get custom events provided by the plugin
     *
     * @return array
     */
    public function getCustomEvents()
    {
    	return ['restoring', 'restored'];
    }

}
