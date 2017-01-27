<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Group;
use TestApp\User;

class GroupMap extends EntityMap
{
    public function users(Group $group)
    {
        return $this->belongsToMany($group, User::class);
    }
}
