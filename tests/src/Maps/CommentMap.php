<?php

namespace TestApp\Maps;

use Analogue\ORM\EntityMap;
use TestApp\Comment;

class CommentMap extends EntityMap
{
    public function commentable(Comment $comment)
    {
        return $this->morphTo($comment);
    }
}
