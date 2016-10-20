<?php

namespace TestApp\Maps;

use TestApp\Group;
use TestApp\User;
use Analogue\ORM\EntityMap;

class GroupMap extends EntityMap 
{
    
    public function users(Group $group)
    {
        return $this->belongsToMany($group, User::class);
    }

}
