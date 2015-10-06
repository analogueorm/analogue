<?php namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class UserMap extends EntityMap
{

    public $timestamps = true;

    protected $connection = 'default';

    protected $embeddables = ['metas' => 'AnalogueTest\App\Meta'];

    public function avatars(User $entity)
    {
        return $this->hasMany($entity, 'AnalogueTest\App\Avatar');
    }

    public function externals(User $entity)
    {
        return $this->hasMany($entity, 'AnalogueTest\App\External');
    }

    public function externalpivots(User $entity)
    {
        return $this->belongsToMany($entity, 'AnalogueTest\App\External', 'user_external');
    }

    public function role(User $entity)
    {
        return $this->belongsTo($entity, 'AnalogueTest\App\Role');
    }

    public function resources(User $entity)
    {
        return $this->morphToMany($entity, 'AnalogueTest\App\Resource', 'resource_owner');
    }
}
