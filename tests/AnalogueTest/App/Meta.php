<?php

namespace AnalogueTest\App;

use Analogue\ORM\Mappable;

class Meta implements Mappable
{
    protected $content = [];

    public function setEntityAttributes(array $attributes)
    {
        $this->setEntityAttribute('metadatas', $attributes['metadatas']);
    }
    
    public function getEntityAttributes()
    {
        return ['metadatas' => $this->getEntityAttribute('metadatas')];
    }
  
    public function setEntityAttribute($key, $value)
    {
        if ($key == 'metadatas') {
            $this->content = unserialize($value);
        }
    }
 
    public function getEntityAttribute($key)
    {
        if ($key == 'metadatas') {
            return serialize($this->content);
        }
    }

    public function all()
    {
        return $this->content;
    }

    public function set($key, $value)
    {
        $this->content[$key] = $value;
    }

    public function get($key)
    {
        return $this->content[$key];
    }
}
