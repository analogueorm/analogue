<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Article;
use TestApp\Blog;

class ArticleMap extends EntityMap
{
    public $timestamps = true;

    public function blog(Article $article)
    {
        return $this->belongsTo($article, Blog::class);
    }
}
