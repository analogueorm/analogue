<?php namespace Analogue\ORM;

use Analogue\ORM\System\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\Drivers\IlluminateDriver;
use Analogue\ORM\Drivers\CapsuleConnectionProvider;

/**
 * This class is a proxy to the Manager class, which allows 
 * using Analogue outside of the Laravel framework.
 */
class Analogue {

    protected static $instance;

    protected static $manager;

    protected static $capsule;

    protected static $booted = false;

    public function __construct(array $connection)
    {
        if(! static::$booted)
        {
            static::$capsule = new Capsule;

            $this->addConnection($connection);

            $this->boot();
        }
    }

    /**
     * Boot Analogue
     * 
     * @return Analogue
     */
    public function boot()
    {
        if (static::$booted)
        {
            return $this;
        }

        $dispatcher = new Dispatcher;

        $connectionProvider = new CapsuleConnectionProvider(static::$capsule);

        $illuminate = new IlluminateDriver($connectionProvider);

        $driverManager = new DriverManager;

        $driverManager->addDriver($illuminate);

        static::$manager = new Manager($driverManager, $dispatcher);
        
        static::$instance = $this;

        static::$booted = true;

        return $this;
    }

    /**
     * Add a connection array to Capsule
     * 
     * @param array     $config 
     * @param string    $name   
     */
    public function addConnection($config, $name = 'default')
    {
        return static::$capsule->addConnection($config, $name);
    }

    /**
     * Get a Database connection object
     *  
     * @param  $name
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        return static::$capsule->getConnection($name);
    }

    /**
     * Dynamically handle static calls to the instance, Facade Style.
     * 
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array(array(static::$instance, $method), $parameters);
    }

    /**
     * Dynamically handle calls to the Analogue Manager instance.
     * 
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array(static::$manager, $method), $parameters);
    }
}
