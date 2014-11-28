<?php namespace Analogue\ORM\Commands;

class Delete extends Command {
	
	public function execute()
	{
		$entity = $this->entity;
		$mapper = $this->mapper;

		if ($mapper->fireEvent('deleting', $entity) === false)
		{
			return false;
		}

		$keyName = $this->entityMap->getKeyName();
		
		$this->query->where($keyName, '=', $this->entity->$keyName)->delete();

		$mapper->fireEvent('deleted', $entity, false);

		return null;
	}

}