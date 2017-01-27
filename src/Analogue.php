<?php

namespace Analogue\ORM;

use Analogue\ORM\Drivers\CapsuleConnectionProvider;
use Analogue\ORM\Drivers\IlluminateDriver;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\System\Manager;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;

/**
 * This class is a proxy to the Manager class, which allows
 * using Analogue outside of the Laravel framework.
 *
 * @mixin Manager
 */
class Analogue
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var Manager
     */
    protected static $manager;

    /**
     * @var Capsule
     */
    protected static $capsule;

    /**
     * @var bool
     */
    protected static $booted = false;

    /**
     * Analogue constructor.
     *
     * @param array $connection
     */
    public function __construct(array $connection)
    {
        if (!static::$booted) {
            static::$capsule = new Capsule();

            $this->addConnection($connection);

            $this->boot();
        }
    }

    /**
     * Boot Analogue.
     *
     * @return Analogue
     */
    public function boot()
    {
        if (static::$booted) {
            return $this;
        }

        $dispatcher = new Dispatcher();

        $connectionProvider = new CapsuleConnectionProvider(static::$capsule);

        $illuminate = new IlluminateDriver($connectionProvider);

        $driverManager = new DriverManager();

        $driverManager->addDriver($illuminate);

        static::$manager = new Manager($driverManager, $dispatcher);

        static::$instance = $this;

        static::$booted = true;

        return $this;
    }

    /**
     * Add a connection array to Capsule.
     *
     * @param array  $config
     * @param string $name
     */
    public function addConnection($config, $name = 'default')
    {
        static::$capsule->addConnection($config, $name);
    }

    /**
     * Get a Database connection object.
     *
     * @param  $name
     *
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        return static::$capsule->getConnection($name);
    }

    /**
     * Dynamically handle static calls to the instance, Facade Style.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::$instance, $method], $parameters);
    }

    /**
     * Dynamically handle calls to the Analogue Manager instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([static::$manager, $method], $parameters);
    }
}
