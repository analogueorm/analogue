<?php

namespace Analogue\ORM;

use Analogue\ORM\Drivers\IlluminateConnectionProvider;
use Analogue\ORM\Drivers\IlluminateDriver;
use Analogue\ORM\Drivers\Manager as DriverManager;
use Analogue\ORM\System\Manager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

/**
 * Integrate Analogue into Laravel.
 */
class AnalogueServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('analogue', function ($app) {
            $db = $app['db'];

            $connectionProvider = new IlluminateConnectionProvider($db);

            $illuminate = new IlluminateDriver($connectionProvider);

            $driverManager = new DriverManager();

            $driverManager->addDriver($illuminate);

            $event = $app->make('events');
            $container = $app->make(Container::class);

            $manager = new Manager($driverManager, $event, $container);

            $manager->registerPlugin(\Analogue\ORM\Plugins\Timestamps\TimestampsPlugin::class);
            $manager->registerPlugin(\Analogue\ORM\Plugins\SoftDeletes\SoftDeletesPlugin::class);

            return $manager;
        });

        $this->app->bind(Manager::class, function ($app) {
            return $app->make('analogue');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['analogue'];
    }
}
