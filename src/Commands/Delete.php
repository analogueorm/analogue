<?php namespace Analogue\ORM\Commands;

use Analogue\ORM\Exceptions\MappingException;

class Delete extends Command {
	
	/**
	 * Execute the Delete Statement
	 * 
	 * @return void
	 */
	public function execute()
	{
		$entity = $this->entity;
		$mapper = $this->mapper;

		if ($mapper->fireEvent('deleting', $entity) === false)
		{
			return false;
		}

		$keyName = $this->entityMap->getKeyName();
		
		$id = $this->entity->getEntityAttribute($keyName);

		if (is_null($id) )
		{
			throw new MappingException('Executed a delete command on an entity with a null primary key');
		}

		$this->query->where($keyName, '=', $id)->delete();

		$mapper->fireEvent('deleted', $entity, false);

		// Once the Entity is successfully deleted, we'll just set the primary key to null.
		$entity->setEntityAttribute($keyName, null);
	}

}
