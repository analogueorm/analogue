<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Article;
use TestApp\Blog;
use TestApp\User;

class BlogMap extends EntityMap
{
    public $timestamps = true;

    public function user(Blog $blog)
    {
        return $this->belongsTo($blog, User::class);
    }

    public function articles(Blog $blog)
    {
        return $this->hasMany($blog, Article::class);
    }
}
