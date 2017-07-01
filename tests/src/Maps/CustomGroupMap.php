<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\CustomGroup;
use TestApp\CustomUser;

class CustomGroupMap extends EntityMap
{
    protected $class = CustomGroup::class;

    protected $table = 'custom_groups';

    protected $primaryKey = 'groupid';

    public function users(CustomGroup $group)
    {
        return $this->belongsToMany($group, CustomUser::class, 'custom_user_groups', 'groupid', 'userid');
    }
}
