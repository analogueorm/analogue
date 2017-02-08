<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Tag;
use TestApp\Blog;

class TagMap extends EntityMap
{
    
    public function blogs(Tag $tag)
    {
        return $this->morphedByMany($tag, Blog::class, 'taggable');
    }

}
