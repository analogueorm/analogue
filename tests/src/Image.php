<?php

namespace TestApp;

class Image
{
    protected $id;

    protected $url;

    protected $size;

    public function __construct($imageUrl, ImageSize $size)
    {
        $this->url = $imageUrl;
        $this->size = $size;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize(ImageSize $size)
    {
        $this->size = $size;
    }

    public function setNullSize()
    {
        $this->size = null;
    }
}
