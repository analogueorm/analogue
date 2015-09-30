<?php namespace Analogue\ORM\System\Proxies;

class EntityProxy extends Proxy {

    /**
     * Underlying entity
     * 
     * @var mixed
     */
    protected $entity;

    /**
     * Load the underlying relation
     * 
     * @return void
     */
    protected function loadOnce()
    {
        $this->entity = $this->load();
    }

    /**
     * Return the actual Entity
     * 
     * @return mixed
     */
    public function getUnderlyingObject()
    {
        if (! $this->isLoaded() ) $this->loadOnce();

        return $this->entity;
    }

    /**
     * Transparently Redirect non overrided calls to the lazy loaded Entity
     *  
     * @param  string $method     
     * @param  array  $parameters 
     * @return mixed             
     */
    public function __call($method, $parameters)
    {
        if (! $this->isLoaded() ) $this->loadOnce();

        return call_user_func_array( [$this->loadedCollection, $method], $parameters);
    }
}
