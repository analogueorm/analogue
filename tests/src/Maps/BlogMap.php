<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Article;
use TestApp\Blog;
use TestApp\Comment;
use TestApp\Tag;
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

    public function comments(Blog $blog)
    {
        return $this->morphMany($blog, Comment::class, 'commentable');
    }

    public function topComment(Blog $blog)
    {
        return $this->morphOne($blog, Comment::class, 'commentable');
    }

    public function tags(Blog $blog)
    {
        return $this->morphToMany($blog, Tag::class, 'taggable');
    }
}
