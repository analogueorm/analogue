<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\CustomGroup;
use TestApp\CustomUser;

class CustomUserMap extends EntityMap
{
    protected $class = CustomUser::class;

    protected $table = 'custom_users';

    public function groups(CustomUser $user)
    {
        return $this->belongsToMany($user, CustomGroup::class, 'custom_user_groups', 'userid', 'groupid');
    }
}
