<?php namespace Analogue\ORM\Plugins\SoftDeletes;

use Carbon\Carbon;
use Analogue\ORM\System\Manager;
use Analogue\ORM\Plugins\AnaloguePluginInterface;

class SoftDeletesPlugin implements AnaloguePluginInterface {


	public function register()
	{
		$host = $this;

		Manager::registerGlobalEvent('initialized', function ($mapper) use ($host)
		{
			$entityMap = $mapper->getEntityMap();

			if($entityMap->usesSoftDeletes())
			{
				$host->registerSoftDelete($mapper);
			}

		});
	}

	protected function registerSoftDelete($mapper)
	{
		$entityMap = $mapper->getEntityMap();

		// Add Scopes
		$mapper->addGlobalScope(new SoftDeletingScope);

		// Register 'deleting' events
		$mapper->registerEvent('deleting', function($entity) use($entityMap) {
					
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
				Manager::mapper(get_class($entity))->store($entity);

				return false;
			}

		});

		// Register RestoreCommand
		$mapper->addCustomCommand('Analogue\ORM\Plugins\SoftDeletes\Restore');
		
		
	}


}