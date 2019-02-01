<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Article;
use TestApp\Blog;
use TestApp\Group;
use TestApp\Identity;
use TestApp\User;

class UserMap extends EntityMap
{
    public $timestamps = true;

    protected $mappings = [
        'remember_token' => 'rememberToken',
    ];

    protected $embeddables = ['identity' => Identity::class];

    public function blog(User $user)
    {
        return $this->hasOne($user, Blog::class);
    }

    public function articles(User $user)
    {
        return $this->hasManyThrough($user, Article::class, Blog::class);
    }

    public function groups(User $user)
    {
        return $this->belongsToMany($user, Group::class);
    }
}
