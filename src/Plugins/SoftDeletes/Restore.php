<?php namespace Analogue\ORM\Plugins\SoftDeletes;

use Analogue\ORM\Commands\Command;

class Restore extends Command {

	public function execute()
	{
		$entity = $this->entity;
		$mapper = $this->mapper;

		if ($mapper->fireEvent('restoring', $entity) === false)
		{
			return false;
		}

		$keyName = $this->entityMap->getKeyName();
		
		$query = $this->query->where($keyName, '=', $entity->getEntityAttribute($keyName));

		$deletedAtColumn = $this->entityMap->getQualifiedDeletedAtColumn();

		$query->update([$deletedAtColumn => null]);
		
		$entity->setEntityAttribute($deletedAtColumn, null);
		
		$mapper->fireEvent('restored', $entity, false);

		$mapper->getEntityCache()->refresh($entity);

		return $entity;
	}

}
