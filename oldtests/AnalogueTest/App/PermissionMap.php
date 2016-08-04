<?php

namespace AnalogueTest\App;

use Analogue\ORM\EntityMap;

class PermissionMap extends EntityMap
{
    public function roles(Permission $permission)
    {
        return $this->belongsToMany($permission, 'AnalogueTest\App\Role', 'role_permission');
    }
}
