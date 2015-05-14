<?php namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class RoleMap extends EntityMap {

    public function users(Role $entity)
    {
        return $this->hasMany($entity, 'AnalogueTest\App\User');
    }

    public function permissions(Role $entity)
    {
        return $this->belongsToMany($entity, 'AnalogueTest\App\Permission', 'role_permission')->withPivot('active');
    }

    public function resources(Role $entity)
    {
        return $this->morphToMany($entity, 'AnalogueTest\App\Resource', 'resource_owner');
    }

}
